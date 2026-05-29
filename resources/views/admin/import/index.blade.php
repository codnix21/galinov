@extends('layouts.app')

@section('title', 'Импорт объявлений')

@section('content')
<div class="mb-8">
    <h1 class="text-4xl font-bold mb-2">Импорт объявлений</h1>
    <p class="text-gray-600">Загрузка из CSV или Excel (XLSX)</p>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-brand-700 hover:underline mt-2 inline-block">← Админ-панель</a>
</div>

<div class="card p-6 max-w-2xl">
    <p class="text-sm text-gray-600 mb-4">
        Колонки: <code>nazvanie</code>, <code>tsena</code> (обязательные),
        <code>gorod</code>, <code>adres</code>, <code>tip</code>, <code>operatsiya</code>,
        <code>status_kod</code>, <code>email_vladelca</code>, <code>opisanie</code>.
    </p>
    <a href="{{ route('admin.import.template') }}" class="btn mb-4 inline-block">Скачать шаблон CSV</a>

    <form method="POST" action="{{ route('admin.import.store') }}" enctype="multipart/form-data" class="space-y-4" data-validate>
        @csrf
        <div>
            <label for="file" class="form-label">Файл CSV или XLSX</label>
            <input type="file" id="file" name="file" class="form-input" accept=".csv,.txt,.xlsx" required>
            @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn-primary">Импортировать</button>
    </form>
</div>
@endsection
