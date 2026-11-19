<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>{{ $title }} — AR</title>
<style>
  html, body { margin:0; height:100%; overflow:hidden; background:#000; font-family:system-ui,-apple-system,sans-serif; }
  #container { position:fixed; inset:0; }
  #overlay { position:fixed; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:14px; color:#fff; text-align:center; padding:24px; background:rgba(0,0,0,.55); z-index:5; }
  #overlay.hidden { display:none; }
  #overlay h1 { font-size:20px; margin:0; }
  #overlay p { font-size:14px; opacity:.85; margin:0; }
  #startBtn { padding:14px 34px; font-size:17px; border:0; border-radius:12px; background:#7c3aed; color:#fff; }
  #loading { font-size:14px; }
  #ytOverlay { position:fixed; inset:0; background:rgba(0,0,0,.92); display:none; align-items:center; justify-content:center; z-index:6; }
  #ytOverlay .box { position:relative; width:min(92vw, 640px); aspect-ratio:16/9; }
  #ytOverlay iframe { position:absolute; inset:0; width:100%; height:100%; border:0; border-radius:10px; }
  #ytClose { position:absolute; top:-44px; right:0; font-size:15px; background:rgba(255,255,255,.15); color:#fff; border:0; border-radius:8px; padding:8px 14px; }
</style>
</head>
<body>
<div id="container"></div>

<div id="overlay">
  <h1>{{ $title }}</h1>
  <div id="status">@if(!$exists)Выпуск не найден.@else Для запуска AR нажмите кнопку. Нужен доступ к камере.@endif</div>
  @if($exists)<button id="startBtn">Запустить камеру</button>@endif
  <button id="soundBtn" type="button" hidden style="padding:10px 18px;font-size:15px;border:0;border-radius:10px;background:rgba(255,255,255,.18);color:#fff;">🔇 Включить звук</button>
  <div id="loading"></div>
</div>

<div id="ytOverlay">
  <div class="box">
    <button id="ytClose" type="button">Закрыть</button>
    <div id="ytPlayer" style="position:absolute;inset:0;"></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/three@0.152.2/build/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/mind-ar@1.2.5/dist/mindar-image-three.prod.js"></script>
<script src="/js/ar-app.js"></script>
</body>
</html>
