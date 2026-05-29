@extends('layouts.app')

@section('title', 'Справочники')

@section('content')
<div class="mb-8">
    <h1 class="text-4xl font-bold mb-2">Справочники</h1>
    <p class="text-gray-600">Настройка без изменения кода</p>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-brand-700 hover:underline mt-2 inline-block">← Админ-панель</a>
</div>

<div class="space-y-8">
    <section class="card p-6">
        <h2 class="text-xl font-bold mb-4">Роли (только просмотр)</h2>
        <ul class="text-sm space-y-1">
            @foreach($roles as $role)
                <li><span class="font-mono text-brand-800">{{ $role->kod }}</span> — {{ $role->nazvanie }}</li>
            @endforeach
        </ul>
    </section>

    <section class="card p-6">
        <h2 class="text-xl font-bold mb-4">Города</h2>
        <form method="POST" action="{{ route('admin.dictionaries.cities.store') }}" class="flex gap-2 mb-4" data-validate>
            @csrf
            <input type="text" name="nazvanie" class="form-input flex-1" placeholder="Название города" required maxlength="255">
            <button type="submit" class="btn-primary">Добавить</button>
        </form>
        @error('nazvanie')<p class="text-sm text-red-600 mb-2">{{ $message }}</p>@enderror
        <ul class="divide-y divide-slate-100">
            @foreach($cities as $city)
                <li class="flex justify-between py-2 text-sm">
                    <span>{{ $city->nazvanie }}</span>
                    <form method="POST" action="{{ route('admin.dictionaries.cities.destroy', $city) }}" data-confirm="Удалить город «{{ $city->nazvanie }}»?">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Удалить</button>
                    </form>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $cities->links() }}</div>
    </section>

    <section class="card p-6">
        <h2 class="text-xl font-bold mb-4">Статусы объявлений</h2>
        <form method="POST" action="{{ route('admin.dictionaries.property-statuses.store') }}" class="grid gap-2 sm:grid-cols-3 mb-4" data-validate>
            @csrf
            <input type="text" name="kod" class="form-input" placeholder="код (draft)" required pattern="[a-z0-9_]+">
            <input type="text" name="nazvanie" class="form-input" placeholder="Название" required>
            <button type="submit" class="btn-primary">Добавить</button>
        </form>
        @foreach($propertyStatuses as $st)
            <form method="POST" action="{{ route('admin.dictionaries.property-statuses.update', $st) }}" class="flex gap-2 items-center py-2 border-b border-slate-50" data-validate>
                @csrf @method('PUT')
                <span class="font-mono text-xs w-28 shrink-0">{{ $st->kod }}</span>
                <input type="text" name="nazvanie" value="{{ $st->nazvanie }}" class="form-input flex-1" required>
                <button type="submit" class="btn text-sm">Сохранить</button>
            </form>
        @endforeach
    </section>

    <section class="card p-6">
        <h2 class="text-xl font-bold mb-4">Статусы договоров</h2>
        <form method="POST" action="{{ route('admin.dictionaries.contract-statuses.store') }}" class="grid gap-2 sm:grid-cols-3 mb-4" data-validate>
            @csrf
            <input type="text" name="kod" class="form-input" placeholder="код (pending)" required pattern="[a-z0-9_]+">
            <input type="text" name="nazvanie" class="form-input" placeholder="Название" required>
            <button type="submit" class="btn-primary">Добавить</button>
        </form>
        @foreach($contractStatuses as $st)
            <form method="POST" action="{{ route('admin.dictionaries.contract-statuses.update', $st) }}" class="flex gap-2 items-center py-2 border-b border-slate-50" data-validate>
                @csrf @method('PUT')
                <span class="font-mono text-xs w-28 shrink-0">{{ $st->kod }}</span>
                <input type="text" name="nazvanie" value="{{ $st->nazvanie }}" class="form-input flex-1" required>
                <button type="submit" class="btn text-sm">Сохранить</button>
            </form>
        @endforeach
    </section>
</div>
@endsection
