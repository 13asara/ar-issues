# Развёртывание: AR-выпуски (Laravel + Node + MindAR)

Стек: PHP 8.2+, MySQL 8 (или MariaDB 10.6+), Node.js 18+, nginx + HTTPS (обязательно:
`getUserMedia`/камера работает только на `https://` или `localhost`).

## 1. Требования на сервере

- PHP 8.2+ с расширениями: `pdo_mysql`, `gd` (QR-PNG), `mbstring`, `fileinfo`, `openssl`, `curl`.
- Composer 2.x.
- Node.js 18+ (проверка: `node -v`).
- ffmpeg (необязательно, но нужен для перекодирования видео в H.264/AAC — см. §7).
- ~1 ГБ диска на выпуск: фото (.mind ~сотни КБ на цель), видео — основная часть.

## 2. Установка кода

```bash
cd /var/www
git clone <ваш-репозиторий> ar-issues && cd ar-issues

cp .env.example .env && nano .env      # DB_*, APP_URL=https://ваш-домен, ADMIN_PASSWORD, MIND_COMPILE_TOKEN
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate
php artisan storage:link               # публичный /storage -> storage/app/public
```

Node-компилятор:

```bash
cd compiler
npm install
```

Проверки прав: `storage/`, `bootstrap/cache/` — запись для пользователя PHP-FPM (обычно `www-data`):

```bash
chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
```

## 3. nginx

```nginx
server {
    listen 443 ssl http2;
    server_name ar.example.com;
    root /var/www/ar-issues/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/ar.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ar.example.com/privkey.pem;

    client_max_body_size 512m;          # видео загружаются через админку

    location /storage/ {
        try_files $uri =404;
        add_header Cache-Control "public, max-age=86400";
    }

    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Сертификат: `certbot --nginx -d ar.example.com` (Let's Encrypt, автообновление).

## 4. Рабочие файлы (структура)

```
storage/app/public/issues/{CODE}/
    images/     00.jpg, 01.jpg ...      (фото-цели, targetIndex = номер файла)
    videos/     00-xYz.mp4 ...          (локальные видео)
    targets.mind                        (результат компиляции — его качает телефон)
storage/app/mind-jobs/                  (job-файлы для node)
```

## 5. Очередь и компиляция

Компиляция `.mind` выполняется фоновым воркером (`CompileMindJob` → `node compile.js`).
Выберите один вариант:

**Вариант А (рекомендуется): supervisor, воркер всегда жив**

```ini
# /etc/supervisor/conf.d/ar-issues-worker.conf
[program:ar-issues-worker]
command=php /var/www/ar-issues/artisan queue:work --tries=1 --timeout=960
autostart=true
autorestart=true
user=www-data
stopwaitsecs=960
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl start ar-issues-worker
```

**Вариант Б (на shared-хостинге без supervisor): cron каждую минуту**

```bash
* * * * * cd /var/www/ar-issues && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

`--stop-when-empty` гарантирует, что задачи не «зависнут»: воркер отработает 55 секунд и завершится,
следующий запуск cron подхватит очередь заново. Частота раз в неделю (1–5 фото) для cron более чем достаточна.

После сборки node сам сообщает PHP: `POST /api/compile/callback` c токеном `MIND_COMPILE_TOKEN`.

## 6. Как проверить, что всё работает

1. `php artisan migrate:status` — миграции применены.
2. Зайти в `https://домен/admin`, ввести `ADMIN_PASSWORD`.
3. Создать выпуск → загрузить 1–2 фото → прикрепить видео → кнопка «Скомпилировать .mind».
4. `tail -f storage/logs/laravel.log` и `supervisorctl tail -f ar-issues-worker` — прогресс компиляции.
5. Статус выпуска должен смениться на `ready`; файл `storage/app/public/issues/{CODE}/targets.mind` существует, а
   `https://домен/storage/issues/{CODE}/targets.mind` открывается в браузере (должен скачать/показать бинарник).
6. `https://домен/api/issues/{CODE}` отдаёт JSON с `mind_url` и списком видео.
7. Открыть QR-код со страницы выпуска на телефоне (`/ar/{CODE}`) → «Запустить камеру» → навести на печатное фото → видео поверх фото.
8. Если что-то не так: статус `failed` — лог виден в админке (блок под статусом); также
   `php artisan queue:failed`.

## 7. Видео: кодеки и размеры

Телефоны (особенно iOS Safari) требуют **H.264 + AAC в MP4**. Перекодируйте перед загрузкой:

```bash
ffmpeg -i input.mov -c:v libx264 -profile:v baseline -level 3.1 -preset fast \
  -vf "scale='min(1080,iw)':-2" -c:a aac -b:a 128k -movflags +faststart output.mp4
```

`-movflags +faststart` обязателен (moov-атом в начале файла — иначе iOS не запустит стрим).
Рекомендуемый битрейт 2–4 Мбит/с, длительность 15–60 сек: файлы по 5–40 МБ.

YouTube-видео: вставьте в админке обычную ссылку (`https://youtu.be/...`). В AR откроется полноэкранный
плеер (iframe), это поведение iOS/Android для стороннего контента.

## 8. Камера / iOS / Android

- Страница AR работает только по HTTPS с валидным сертификатом — иначе браузер не даст доступ к камере.
- Разрешение на камеру браузер запрашивает при первом запуске; iOS начиная с 14.x хранит его для домена.
- Автовоспроизведение звука на iOS запрещено: видео стартует без звука, кнопка «🔇 Включить звук» появляется
  после запуска и жестом разрешает включить звук.
- Если телефон слабый — открывайте ссылку без параметров (трекинг одной цели одновременно);
  ссылка с `?multi=1` включает одновременный трекинг всех целей (нагружает CPU).

## 9. Резервные копии и обслуживание

- Бэкапьте: БД (`mysqldump ar_issues`) + `storage/app/public/issues/` + `.env`.
- Логи компиляции хранятся в `issues.compile_log` (видно в админке), логи приложения — `storage/logs/laravel.log`.
- После обновления кода: `composer install --no-dev`, `php artisan migrate --force`, `php artisan queue:restart`.
