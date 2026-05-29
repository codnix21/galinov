@extends('layouts.app')

@section('title', 'Импорт объявлений')

@section('content')
<div class="mb-8">
    <h1 class="text-4xl font-bold mb-2">Импорт объявлений</h1>
    <p class="text-gray-600">Загрузка из CSV или Excel (XLSX). Первая строка — заголовки на русском.</p>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-brand-700 hover:underline mt-2 inline-block">← Админ-панель</a>
</div>

<div class="card p-6 max-w-3xl mb-6">
    <h2 class="text-lg font-bold mb-3">Колонки файла</h2>
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left border-b border-slate-200">
                    <th class="p-2 font-semibold">Колонка</th>
                    <th class="p-2 font-semibold">Обязательно</th>
                    <th class="p-2 font-semibold">Подсказка</th>
                </tr>
            </thead>
            <tbody>
                @foreach($columns as $col)
                    <tr class="border-b border-slate-100">
                        <td class="p-2 font-medium">{{ $col['label'] }}</td>
                        <td class="p-2">{{ $col['required'] ? 'Да' : 'Нет' }}</td>
                        <td class="p-2 text-slate-600">{{ $col['hint'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-slate-500 mt-3">
        В шаблоне CSV заголовки на русском. При импорте также принимаются латинские имена (nazvanie, tsena и т.д.).
        Разделитель в CSV — точка с запятой (;).
    </p>
</div>

<div class="card p-6 max-w-2xl">
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
