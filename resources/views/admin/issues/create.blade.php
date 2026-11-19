@extends('layouts.admin')

@section('title', 'Новый выпуск')

@section('content')
<div class="card" style="max-width:520px;">
  <h1>Новый выпуск</h1>
  <form method="post" action="{{ route('admin.issues.store') }}">
    @csrf
    <div class="field">
      <label for="title">Название выпуска</label>
      <input type="text" id="title" name="title" required placeholder="Выпуск №12 — 1 марта">
    </div>
    <button class="btn">Создать</button>
  </form>
</div>
@endsection
