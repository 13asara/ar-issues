(function () {
  'use strict';

  var container = document.getElementById('container');
  var statusEl = document.getElementById('status');
  var loadingEl = document.getElementById('loading');
  var startBtn = document.getElementById('startBtn');
  var overlay = document.getElementById('overlay');
  var ytOverlay = document.getElementById('ytOverlay');
  var ytPlayer = document.getElementById('ytPlayer');

  var code = decodeURIComponent(location.pathname.split('/').pop() || '');
  var mindar = null;
  var videos = [];

  function setMessage(text) {
    statusEl.textContent = text;
  }

  function fetchConfig() {
    return fetch('/api/issues/' + encodeURIComponent(code), {
      headers: { Accept: 'application/json' }
    }).then(function (res) {
      if (res.status === 404) throw new Error('Выпуск не найден');
      return res.json();
    }).then(function (cfg) {
      if (cfg.status !== 'ready' || !cfg.mind_url) {
        throw new Error('Выпуск ещё обрабатывается, зайдите позже');
      }
      return cfg;
    });
  }

  function createLocalVideo(cfg, target) {
    var video = document.createElement('video');
    video.src = target.video.url;
    video.crossOrigin = 'anonymous';
    video.loop = true;
    video.playsInline = true;
    video.setAttribute('playsinline', '');
    video.setAttribute('webkit-playsinline', '');
    video.muted = true;
    video.preload = 'auto';
    video.style.width = '1px';
    video.style.height = '1px';
    video.style.position = 'fixed';
    video.style.opacity = '0';
    video.style.pointerEvents = 'none';
    document.body.appendChild(video);
    videos.push(video);
    return video;
  }

  function startYouTube(video) {
    ytOverlay.style.display = 'flex';
    ytPlayer.innerHTML = '<iframe src="https://www.youtube-nocookie.com/embed/' + encodeURIComponent(video.youtube_id) +
      '?autoplay=1&playsinline=1&rel=0" allow="autoplay; encrypted-media; fullscreen; picture-in-picture" allowfullscreen></iframe>';
  }

  function stopYouTube() {
    ytOverlay.style.display = 'none';
    ytPlayer.innerHTML = '';
  }

  function startAR(cfg) {
    var maxTrack = Math.min(cfg.targets.length, new URLSearchParams(location.search).get('multi') === '1' ? cfg.targets.length : 1);

    mindar = new window.MINDAR.IMAGE.MindARThree({
      container: container,
      imageTargetSrc: cfg.mind_url,
      maxTrack: maxTrack,
      uiLoading: 'yes',
      uiScanning: 'yes',
      uiError: 'no'
    });

    var tracks = [];

    cfg.targets.forEach(function (target) {
      var anchor = mindar.addAnchor(target.target_index);
      var video = null;

      if (target.video && target.video.type === 'local') {
        video = createLocalVideo(cfg, target);
        var aspect = target.aspect && target.aspect > 0 ? target.aspect : 1;
        var geometry = new THREE.PlaneGeometry(1, aspect);
        var material = new THREE.MeshBasicMaterial({
          map: new THREE.VideoTexture(video),
          transparent: true,
          toneMapped: false
        });
        var mesh = new THREE.Mesh(geometry, material);
        anchor.group.add(mesh);
        anchor.group.visible = false;
        anchor.onTargetFound = function () {
          video.play().catch(function () {
            setMessage('Нажмите на экран, чтобы воспроизвести видео');
            var resume = function () {
              video.play();
              container.removeEventListener('click', resume);
            };
            container.addEventListener('click', resume);
          });
        };
        anchor.onTargetLost = function () {
          video.pause();
        };
      } else if (target.video && target.video.type === 'youtube') {
        anchor.group.visible = false;
        anchor.onTargetFound = function () {
          if (videos.length) videos.forEach(function (v) { v.pause(); });
          startYouTube(target.video);
        };
        anchor.onTargetLost = stopYouTube;
      }

      tracks.push(anchor);
    });

    document.getElementById('ytClose').addEventListener('click', function () {
      stopYouTube();
      if (mindar) mindar.stop();
    });

    return mindar.start().then(function () {
      overlay.classList.add('hidden');
      setupSound();
    }).catch(function (err) {
      setMessage('Не удалось запустить камеру: ' + (err && err.message ? err.message : 'ошибка') +
        (window.isSecureContext ? '' : '. Приложению нужен HTTPS'));
    });
  }

  function setupSound() {
    var btn = document.getElementById('soundBtn');
    btn.hidden = false;
    var soundOn = false;
    btn.addEventListener('click', function () {
      soundOn = !soundOn;
      videos.forEach(function (v) {
        v.muted = !soundOn;
        if (soundOn) {
          v.play().catch(function () {});
        }
      });
      btn.textContent = soundOn ? '🔊 Выключить звук' : '🔇 Включить звук';
    });
  }

  if (!startBtn) {
    setMessage('Выпуск не найден. Проверьте ссылку из QR-кода.');
    return;
  }

  startBtn.addEventListener('click', function () {
    startBtn.disabled = true;
    setMessage('Загружаем AR-библиотеку и компилированные цели…');

    var cfg;
    fetchConfig()
      .then(function (data) {
        cfg = data;
        return startAR(cfg);
      })
      .then(function () {
        return startAR(cfg);
      })
      .catch(function (err) {
        startBtn.disabled = false;
        setMessage(err.message || 'Ошибка загрузки выпуска');
      });
  });

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      videos.forEach(function (v) { v.pause(); });
      stopYouTube();
    }
  });
})();
