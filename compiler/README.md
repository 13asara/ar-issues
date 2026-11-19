# Node-компилятор .mind

Использует официальный `OfflineCompiler` из npm-пакета `mind-ar` (Image Tracking).
Нативная зависимость `canvas` замещена на `@napi-rs/canvas` (override в package.json),
поэтому сборка не требует системных библиотек (cairo/pango) и предсобранных бинарников
для конкретной версии Node.

## Установка

```bash
cd compiler
npm install
```

Требуется Node.js 18+.

## Запуск

PHP создаёт job-файл и вызывает:

```bash
node compile.js --job storage/app/mind-jobs/<code>-xxxxxx.json
```

Формат job-файла:

```json
{
  "issue": "A1B2C3D4",
  "images": ["/abs/path/img00.jpg", "/abs/path/img01.jpg"],
  "output": "/abs/path/storage/app/public/issues/A1B2C3D4/targets.mind",
  "callback": {
    "url": "https://site.example/api/compile/callback",
    "token": " secret из .env (MIND_COMPILE_TOKEN) ",
    "issue": "A1B2C3D4"
  }
}
```

После записи `targets.mind` скрипт делает POST на `callback.url`
(заголовок `X-Mind-Token`, тело `{issue, status, log}`) и PHP переводит выпуск
в статус `ready`.

Ручной прогон (без PHP) — положите job-файл рядом и запустите команду выше.
