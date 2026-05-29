{{-- Все объявления в админке. --}}
@extends('layouts.app')

@section('title', 'Управление объявлениями')

@section('content')
@php
    $hasFilters = request()->hasAny(['search', 'type', 'operation', 'status', 'city_id', 'min_price', 'max_price', 'sort']);
@endphp
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-4xl font-bold mb-2">Управление объявлениями</h1>
            <p class="text-gray-600">
                @if($hasFilters ?? false)
                    Найдено: {{ $properties->total() }}
                @else
                    Всего объявлений: {{ $properties->total() }}
                @endif
            </p>
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
    
    <div class="card p-4 mb-6 catalog-filters">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Фильтры</h2>
        <form method="GET" action="{{ route('admin.properties') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="sm:col-span-2 lg:col-span-4">
                <label class="form-label" for="admin-prop-search">Поиск</label>
                <input type="text" id="admin-prop-search" name="search" value="{{ request('search') }}" class="form-input w-full" placeholder="Название, описание, город, адрес…">
            </div>
            <div>
                <label class="form-label" for="admin-prop-status">Статус</label>
                <select id="admin-prop-status" name="status" class="form-input w-full">
                    @foreach($statusOptions ?? [] as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="admin-prop-type">Тип</label>
                <select id="admin-prop-type" name="type" class="form-input w-full">
                    <option value="">Все</option>
                    @foreach(\App\Models\Property::tipNazvaniya() as $value => $label)
                        <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="admin-prop-operation">Операция</label>
                <select id="admin-prop-operation" name="operation" class="form-input w-full">
                    <option value="">Все</option>
                    <option value="sale" {{ request('operation') === 'sale' ? 'selected' : '' }}>Продажа</option>
                    <option value="rent" {{ request('operation') === 'rent' ? 'selected' : '' }}>Аренда</option>
                </select>
            </div>
            <div>
                <label class="form-label" for="admin-prop-city">Город</label>
                <select id="admin-prop-city" name="city_id" class="form-input w-full">
                    <option value="">Все города</option>
                    @foreach($cities ?? [] as $city)
                        <option value="{{ $city->id }}" {{ (string) request('city_id') === (string) $city->id ? 'selected' : '' }}>{{ $city->nazvanie }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="admin-prop-min-price">Цена от (₽)</label>
                <input type="number" id="admin-prop-min-price" name="min_price" value="{{ request('min_price') }}" min="0" class="form-input w-full" placeholder="0">
            </div>
            <div>
                <label class="form-label" for="admin-prop-max-price">Цена до (₽)</label>
                <input type="number" id="admin-prop-max-price" name="max_price" value="{{ request('max_price') }}" min="0" class="form-input w-full">
            </div>
            <div>
                <label class="form-label" for="admin-prop-sort">Сортировка</label>
                <select id="admin-prop-sort" name="sort" class="form-input w-full">
                    @foreach(\App\Support\PropertyCatalogFilter::sortOptions() as $value => $label)
                        <option value="{{ $value }}" {{ request('sort', 'newest') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">Применить</button>
                @if($hasFilters)
                    <a href="{{ route('admin.properties') }}" class="btn">Сбросить</a>
                @endif
            </div>
        </form>
    </div>

    @if($properties->count() > 0)
    <form id="bulk-properties-form" method="POST" action="{{ route('admin.bulk.properties') }}" class="card p-4 mb-6 flex flex-wrap items-end gap-3"
          data-confirm="Изменить статус у всех отмеченных объявлений?" data-confirm-title="Массовая операция">
        @csrf
        <span class="text-sm font-medium text-slate-700 w-full sm:w-auto">Массовые действия:</span>
        <select name="status_kod" class="form-input w-full sm:w-48" required>
            @foreach($propertyStatuses ?? [] as $st)
                <option value="{{ $st->kod }}">{{ $st->nazvanie }}</option>
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

