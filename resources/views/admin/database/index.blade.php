@extends('layouts.app')

@section('title', 'Резервное копирование БД')

@section('content')
<div class="mb-8">
    <h1 class="text-4xl font-bold mb-2">База данных</h1>
    <p class="text-gray-600">Резервное копирование и восстановление (драйвер: {{ $driver }})</p>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-brand-700 hover:underline mt-2 inline-block">← Админ-панель</a>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="card p-6">
        <h2 class="text-xl font-bold mb-4">Создать копию</h2>
        <form method="POST" action="{{ route('admin.database.backup') }}" data-confirm="Создать резервную копию базы данных?" data-confirm-title="Резервное копирование">
            @csrf
            <button type="submit" class="btn-primary">Создать backup</button>
        </form>
        <p class="text-sm text-gray-500 mt-3">Для MySQL нужен mysqldump в контейнере PHP. SQLite копируется файлом.</p>
    </div>

    <div class="card p-6">
        <h2 class="text-xl font-bold mb-4">Восстановить</h2>
        <form method="POST" action="{{ route('admin.database.restore') }}" enctype="multipart/form-data" class="space-y-4"
              data-confirm="ВНИМАНИЕ: текущие данные будут перезаписаны. Продолжить?"
              data-confirm-title="Восстановление БД">
            @csrf
            <div>
                <label class="form-label">Файл из списка</label>
                <select name="backup_file" class="form-input">
                    <option value="">— выберите —</option>
                    @foreach($backups as $b)
                        <option value="{{ $b['name'] }}">{{ $b['name'] }} ({{ $b['created_at'] }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Или загрузить .sql / .sqlite</label>
                <input type="file" name="upload_sql" class="form-input" accept=".sql,.txt,.sqlite">
            </div>
            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" name="confirm_restore" value="1" required class="mt-1">
                <span>Понимаю, что все текущие данные будут заменены содержимым копии.</span>
            </label>
            @error('confirm_restore')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <button type="submit" class="btn bg-red-600 text-white border-red-600 hover:bg-red-700">Восстановить</button>
        </form>
    </div>
</div>

<div class="card p-6 mt-6">
    <h2 class="text-xl font-bold mb-4">Сохранённые копии</h2>
    @if($backups === [])
        <p class="text-gray-500">Копий пока нет.</p>
    @else
        <ul class="divide-y divide-slate-100">
            @foreach($backups as $b)
                <li class="flex items-center justify-between py-3">
                    <div>
                        <span class="font-medium">{{ $b['name'] }}</span>
                        <span class="text-sm text-gray-500 ml-2">{{ number_format($b['size'] / 1024, 1) }} КБ · {{ $b['created_at'] }}</span>
                    </div>
                    <a href="{{ route('admin.database.download', $b['name']) }}" class="btn text-sm">Скачать</a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
