@extends('layouts.admin')

@section('title', $issue->title)

@section('content')
<div class="card">
  <h1>{{ $issue->title }} <span class="badge {{ $issue->status }}">{{ $issue->status }}</span></h1>
  <p class="muted">
    Код выпуска: <code style="font-size:15px;">{{ $issue->code }}</code><br>
    AR-ссылка: <a href="{{ $issue->arUrl() }}" target="_blank">{{ $issue->arUrl() }}</a><br>
    .mind-файл: <code>{{ $issue->status === 'ready' ? $issue->mindUrl() : '— (ещё не скомпилирован)' }}</code>
  </p>

  <div style="display:flex; gap:14px; align-items:center; flex-wrap:wrap; margin-top:14px;">
    <form method="post" action="{{ route('admin.issues.compile', $issue) }}" onsubmit="this.querySelector('button').disabled=true">
      @csrf
      <button class="btn">Скомпилировать .mind</button>
    </form>
    <img src="{{ route('admin.issues.qr', $issue) }}" alt="QR: {{ $issue->arUrl() }}" width="140" height="140" style="border:1px solid var(--border);border-radius:8px;background:#fff;">
    <div class="muted">QR-код (для печати) — откройте <a href="{{ route('admin.issues.qr', $issue) }}" target="_blank">PNG</a></div>
  </div>

  @if ($issue->status === 'compiling')
    <p class="muted" style="margin-top:10px;">Компиляция идёт — обновите страницу через минуту.</p>
  @elseif ($issue->status === 'failed')
    <p class="alert error" style="margin-top:14px;">Компиляция завершилась ошибкой. Лог:</p>
    <pre class="log">{{ $issue->compile_log }}</pre>
  @elseif ($needsRecompile)
    <p class="alert error" style="margin-top:14px;">Состав фото менялся после последней компиляции — перекомпилируйте выпуск.</p>
  @endif
</div>

<div class="card">
  <h2>Целевые фото → видео (targetIndex присваивается по порядку с 0)</h2>
  <form method="post" action="{{ route('admin.issues.images.upload', $issue) }}" enctype="multipart/form-data">
    @csrf
    <div class="field">
      <label>Загрузить фото (JPG/PNG, от 200px; до 8 шт. за раз — архитектура до 8–10 целей на выпуск)</label>
      <input type="file" name="images[]" accept="image/jpeg,image/png" multiple required>
    </div>
    <button class="btn secondary">Загрузить фото</button>
  </form>
</div>

@forelse ($issue->images as $image)
  @php $video = $image->videos->first(); @endphp
  <div class="card">
    <div class="grid">
      <img class="thumb" src="{{ $image->imageUrl() }}" alt="target {{ $image->target_index }}">
      <div>
        <p style="margin:0 0 6px;"><strong>targetIndex: {{ $image->target_index }}</strong> — {{ $image->original_name }} ({{ $image->width }}×{{ $image->height }})</p>

        @if ($video)
          <p style="margin:0 0 6px;">
            Видео: <span class="badge {{ $video->source_type === 'youtube' ? 'compiling' : 'ready' }}">{{ $video->source_type }}</span>
            @if ($video->source_type === 'youtube')
              YouTube ID: <code>{{ $video->youtube_id }}</code>
            @else
              <code>{{ $video->disk_path }}</code>
            @endif
          </p>
        @else
          <p class="muted" style="margin:0 0 6px;">Видео не прикреплено</p>
        @endif

        <form method="post" action="{{ route('admin.issues.videos.save', [$issue, $image]) }}" enctype="multipart/form-data" style="margin-top:10px;">
          @csrf
          <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
            <div>
              <label>Тип видео</label>
              <select name="source_type" id="st-{{ $image->id }}" onchange="vf_{{ $image->id }}.style.display=this.value==='local'?'block':'none'; yf_{{ $image->id }}.style.display=this.value==='youtube'?'block':'none'">
                <option value="local">Файл на сервере (mp4)</option>
                <option value="youtube" @selected($video?->source_type === 'youtube')>YouTube</option>
              </select>
            </div>
            <div id="vf_{{ $image->id }}" style="@unless($video?->source_type === 'youtube')display:block;@else display:none;@endunless">
              <label>MP4 файл</label>
              <input type="file" name="video_file" accept="video/mp4">
            </div>
            <div id="yf_{{ $image->id }}" style="@if($video?->source_type === 'youtube')display:block;@else display:none;@endif">
              <label>YouTube ссылка или ID</label>
              <input type="text" name="youtube" value="{{ $video?->youtube_id }}" placeholder="https://youtu.be/...">
            </div>
            <button class="btn secondary">Прикрепить</button>
          </div>
        </form>
      </div>
      <form method="post" action="{{ route('admin.issues.images.delete', [$issue, $image]) }}" onsubmit="return confirm('Удалить фото и его видео?')">
        @csrf
        @method('DELETE')
        <button class="btn danger">Удалить фото</button>
      </form>
    </div>
  </div>
@empty
  <div class="card"><p class="muted">Фото ещё не загружены.</p></div>
@endforelse
@endsection
