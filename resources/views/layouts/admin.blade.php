<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'AR выпуски')</title>
<style>
  :root { --accent:#7c3aed; --bg:#f6f7fb; --border:#e5e7eb; }
  * { box-sizing: border-box; }
  body { margin:0; font-family: system-ui, -apple-system, sans-serif; background:var(--bg); color:#111827; }
  header { background:#fff; border-bottom:1px solid var(--border); padding:14px 24px; display:flex; align-items:center; gap:18px; }
  header .brand { font-weight:700; color:var(--accent); text-decoration:none; font-size:18px; }
  header nav { margin-left:auto; display:flex; gap:12px; }
  main { max-width: 960px; margin: 24px auto; padding: 0 16px; }
  .card { background:#fff; border:1px solid var(--border); border-radius:12px; padding:20px; margin-bottom:18px; }
  h1 { font-size:22px; margin:0 0 16px; } h2 { font-size:17px; margin-top:0; }
  table { width:100%; border-collapse:collapse; }
  th, td { text-align:left; padding:10px 8px; border-bottom:1px solid var(--border); font-size:14px; vertical-align:top; }
  .btn { display:inline-block; background:var(--accent); color:#fff; border:0; border-radius:8px; padding:9px 16px; font-size:14px; cursor:pointer; text-decoration:none; }
  .btn.secondary { background:#eef0f4; color:#111827; }
  .btn.danger { background:#dc2626; }
  .btn:disabled { opacity:.5; cursor:not-allowed; }
  .badge { display:inline-block; padding:3px 10px; border-radius:99px; font-size:12px; font-weight:600; }
  .badge.draft { background:#e5e7eb; color:#374151; }
  .badge.compiling { background:#fef3c7; color:#92400e; }
  .badge.ready { background:#dcfce7; color:#166534; }
  .badge.failed { background:#fee2e2; color:#991b1b; }
  .field { margin-bottom:14px; }
  label { display:block; font-size:13px; font-weight:600; margin-bottom:4px; }
  input[type=text], input[type=password], select { width:100%; padding:9px 11px; border:1px solid var(--border); border-radius:8px; font-size:14px; }
  .muted { color:#6b7280; font-size:13px; }
  .alert { padding:12px 16px; border-radius:8px; margin-bottom:14px; font-size:14px; }
  .alert.success { background:#dcfce7; color:#166534; }
  .alert.error { background:#fee2e2; color:#991b1b; }
  .thumb { max-width:120px; max-height:90px; border-radius:6px; display:block; }
  .grid { display:grid; grid-template-columns: 160px 1fr auto; gap:16px; align-items:start; }
  pre.log { background:#0f172a; color:#e2e8f0; padding:12px; border-radius:8px; font-size:12px; overflow:auto; max-height:260px; white-space:pre-wrap; }
</style>
</head>
<body>
<header>
  <a class="brand" href="{{ route('admin.issues.index') }}">AR выпуски</a>
  <nav>
    @if (session('admin_authenticated'))
      <a class="btn secondary" href="{{ route('admin.issues.create') }}">+ Новый выпуск</a>
      <form method="post" action="{{ route('admin.logout') }}">@csrf
        <button class="btn secondary">Выйти</button>
      </form>
    @endif
  </nav>
</header>
<main>
  @if (session('success'))<div class="alert success">{{ session('success') }}</div>@endif
  @if (session('error'))<div class="alert error">{{ session('error') }}</div>@endif
  @if ($errors->any())
    <div class="alert error">
      <ul style="margin:0;padding-left:18px;">
      @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
      </ul>
    </div>
  @endif
  @yield('content')
</main>
</body>
</html>
