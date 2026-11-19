# AR-выпуски: печатное фото → QR → видео поверх (MindAR)

Минимальная система для «AR-издания»: раз в неделю создаётся выпуск из 1–5 фотографий,
к каждой прикрепляется видео. QR-код ведёт на `/ar/{code}`, где MindAR распознаёт
фото с камеры и накладывает видео.

## Состав

- **Laravel 11** (PHP 8.2+): админка `/admin` (пароль из `ADMIN_PASSWORD`), MySQL.
- **API**: `GET /api/issues/{code}` — конфиг выпуска (ссылка на `.mind`, список целей
  с `target_index` и видео). `POST /api/compile/callback` — отчёт node-компилятора (токен).
- **QR**: `GET /admin/issues/{id}/qr.png` — PNG для печати, кодирует `APP_URL/ar/{code}`.
- **AR-страница**: `/ar/{code}` — three.js + `mind-ar@1.2.5` (mindar-image-three), локальные
  mp4 (текстура на плоскости поверх якоря) или YouTube (iframe поверх AR).
- **Node-компилятор**: `compiler/compile.js` — официальный `OfflineCompiler` из `mind-ar`;
  результат кладёт в `storage/app/public/issues/{code}/targets.mind` и сообщает PHP.

## Поток данных

```
админ: создать выпуск → загрузить N фото → прикрепить видео (mp4/YouTube)
      ↓ «Скомпилировать .mind»
Laravel: статус=compiling → очередь (БД) → CompileMindJob → node compile.js
      ↓
node: собирает targets.mind → POST /api/compile/callback
      ↓
Laravel: статус=ready → QR/AR-ссылка рабочая
```

`targetIndex` в `.mind` = порядковый номер загруженного фото (0,1,2...),
привязка видео — по этому индексу (`target_images.target_index`).

## Быстрый старт (локально)

```bash
cp .env.example .env
composer install
php artisan key:generate && php artisan migrate
php artisan storage:link
cd compiler && npm install && cd ..
php artisan serve
php artisan queue:work --tries=1
```

Камера в браузере требует HTTPS или `localhost` — полноценную проверку AR выполняйте на продакшене.

Продакшен и проверка: см. [DEPLOYMENT.md](DEPLOYMENT.md).

## Ограничения и правила

- 8–10 целей на выпуск — практический предел MindAR image tracking (по умолчанию включён
  трекинг 1 цели одновременно; `?multi=1` — все).
- Фото: JPG/PNG, контрастные, с большим числом деталей; минимум 200×200 px.
- Видео: локальные — H.264/AAC mp4 + `faststart`; команды перекодирования в DEPLOYMENT.md §7.
- Компиляция одного выпуска занимает от секунд до пары минут (зависит от числа фото).
