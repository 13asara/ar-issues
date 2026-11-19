@extends('layouts.admin')

@section('title', 'Выпуски')

@section('content')
<div class="card">
  <h1>Выпуски</h1>
  @forelse ($issues as $issue)
    <table>
      <thead><tr><th>Название</th><th>Код</th><th>Фото</th><th>Статус</th><th></th></tr></thead>
      <tbody>
        <tr>
          <td><a href="{{ route('admin.issues.show', $issue) }}">{{ $issue->title }}</a></td>
          <td><code>{{ $issue->code }}</code></td>
          <td>{{ $issue->images_count }}</td>
          <td><span class="badge {{ $issue->status }}">{{ $issue->status }}</span></td>
          <td>
            <a class="btn secondary" href="{{ route('admin.issues.show', $issue) }}">Открыть</a>
          </td>
        </tr>
      </tbody>
    </table>
  @empty
    <p class="muted">Пока нет выпусков. <a href="{{ route('admin.issues.create') }}">Создать первый</a></p>
  @endforelse
</div>
@endsection
