@extends('layouts.admin')

@section('title', 'Вход')

@section('content')
<div class="card" style="max-width:420px;margin:60px auto;">
  <h1>Вход в админ-панель</h1>
  <form method="post" action="{{ route('admin.login.post') }}">
    @csrf
    <div class="field">
      <label for="password">Пароль администратора</label>
      <input type="password" id="password" name="password" required autofocus>
    </div>
    <button class="btn">Войти</button>
  </form>
</div>
@endsection
