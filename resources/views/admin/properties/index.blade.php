{{-- Все объявления в админке. --}}
@extends('layouts.app')

@section('title', 'Управление объявлениями')

@section('content')
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-4xl font-bold mb-2">Управление объявлениями</h1>
            <p class="text-gray-600">Всего объявлений: {{ $properties->total() }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.dashboard') }}" class="btn">
                ← Назад
            </a>
            <a href="{{ route('admin.properties.create') }}" class="btn-primary">
                + Создать объявление
            </a>
        </div>
    </div>
    
    <!-- Поиск -->
    <div class="card p-4 mb-6">
        <form method="GET" action="{{ route('admin.properties') }}" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input flex-1" placeholder="Поиск по названию, описанию, городу, адресу...">
            <button type="submit" class="btn-primary">Поиск</button>
            @if(request('search'))
                <a href="{{ route('admin.properties') }}" class="btn">Сбросить</a>
            @endif
        </form>
    </div>

    @if($properties->count() > 0)
    <form id="bulk-properties-form" method="POST" action="{{ route('admin.bulk.properties') }}" class="card p-4 mb-6 flex flex-wrap items-end gap-3"
          data-confirm="Изменить статус у всех отмеченных объявлений?" data-confirm-title="Массовая операция">
        @csrf
        <span class="text-sm font-medium text-slate-700 w-full sm:w-auto">Массовые действия:</span>
        <select name="status_kod" class="form-input w-full sm:w-48" required>
            @foreach($propertyStatuses ?? [] as $st)
                <option value="{{ $st->kod }}">{{ $st->nazvanie }} ({{ $st->kod }})</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="confirm_bulk" value="1" required>
            Подтверждаю
        </label>
        <button type="submit" class="btn-primary">Применить к выбранным</button>
    </form>
    @endif
</div>

@if($properties->count() > 0)
    <div class="space-y-4">
        @foreach($properties as $property)
            <div class="card p-6">
                <div class="flex items-start justify-between gap-3">
                    <label class="flex items-start pt-1 shrink-0">
                        <input type="checkbox" name="ids[]" value="{{ $property->id }}" form="bulk-properties-form" class="mt-1 rounded border-slate-300">
                    </label>
                    <div class="flex-1">
                        <div class="mb-3 flex items-center gap-2 flex-wrap">
                            <span class="badge">{{ $property->type_name }}</span>
                            <span class="badge">{{ $property->operation_name }}</span>
                            <span class="badge">{{ $property->status_name }}</span>
                        </div>
                        <h3 class="text-xl font-bold mb-2">
                            <a href="{{ route('properties.show', $property) }}" class="hover:underline">
                                {{ $property->nazvanie }}
                            </a>
                        </h3>
                        <p class="text-gray-600 mb-3">
                            {{ $property->gorod ?? 'Не указан' }}, {{ $property->adres_ulitsy ?? 'Не указан' }}
                        </p>
                        <div class="flex items-center gap-6 text-sm text-gray-600">
                            <span>Риэлтор: <span class="font-medium">{{ trim($property->user->familia . ' ' . $property->user->imya . ' ' . $property->user->otchestvo) }}</span></span>
                            <span>Цена: <span class="font-medium">{{ number_format($property->tsena, 0, ',', ' ') }} ₽</span></span>
                            <span>Создано: <span class="font-medium">{{ $property->sozdano_at ? $property->sozdano_at->format('d.m.Y') : ($property->created_at ? $property->created_at->format('d.m.Y') : 'Не указана') }}</span></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 ml-6">
                        <a href="{{ route('properties.show', $property) }}" class="btn">
                            Просмотр
                        </a>
                        <a href="{{ route('admin.properties.edit', $property) }}" class="btn">
                            Редактировать
                        </a>
                        <form method="POST" action="{{ route('admin.properties.delete', $property) }}" class="inline delete-form" data-type="объявление" data-name="{{ $property->nazvanie }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn hover:bg-red-600 hover:border-red-600 hover:text-white">
                                Удалить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8">
        {{ $properties->links() }}
    </div>
@else
    <div class="card p-12 text-center">
        <p class="text-xl text-gray-600">Объявления не найдены</p>
    </div>
@endif
@endsection

