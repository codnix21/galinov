@extends('layouts.app')

@section('title', 'Мои объекты')

@section('content')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-4xl font-bold mb-2">Мои объекты</h1>
        <p class="text-gray-600">Портфель объявлений с фильтрами по статусу</p>
    </div>
    <a href="{{ route('realtor.dashboard') }}" class="btn">← Рабочее место</a>
</div>

<div class="card p-6 mb-8 catalog-filters">
    <form method="GET" action="{{ route('realtor.properties') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
            <label class="form-label">Поиск</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Название или адрес">
        </div>
        <div>
            <label class="form-label">Статус</label>
            <select name="status" class="form-input">
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Тип</label>
            <select name="type" class="form-input">
                <option value="">Все</option>
                @foreach(\App\Models\Property::tipNazvaniya() as $value => $label)
                    <option value="{{ $value }}" {{ request('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Операция</label>
            <select name="operation" class="form-input">
                <option value="">Все</option>
                <option value="sale" {{ request('operation') == 'sale' ? 'selected' : '' }}>Продажа</option>
                <option value="rent" {{ request('operation') == 'rent' ? 'selected' : '' }}>Аренда</option>
            </select>
        </div>
        <div class="md:col-span-4 flex gap-3">
            <button type="submit" class="btn-primary">Применить</button>
            <a href="{{ route('realtor.properties') }}" class="btn">Сбросить</a>
            <a href="{{ route('properties.create') }}" class="btn ml-auto">+ Создать</a>
        </div>
    </form>
</div>

@if($properties->count() > 0)
    <div class="space-y-4">
        @foreach($properties as $property)
            <div class="card p-6 flex flex-wrap gap-4 items-center">
                <div class="w-24 h-20 rounded-lg overflow-hidden bg-gray-100 shrink-0">
                    @if($property->images->count() > 0)
                        <img src="{{ $property->images->first()->public_url }}" alt="" class="w-full h-full object-cover">
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-lg">{{ $property->nazvanie }}</h3>
                    <p class="text-sm text-gray-600">{{ $property->gorod }} · {{ $property->type_name }} · {{ $property->operation_name }}</p>
                    <span class="badge mt-1">{{ $property->status_name }}</span>
                    @if($property->prichina_otkaza_mod)
                        <p class="text-xs text-amber-800 mt-1">Отказ: {{ Str::limit($property->prichina_otkaza_mod, 80) }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <div class="text-xl font-bold">{{ number_format($property->tsena, 0, ',', ' ') }} ₽</div>
                    <div class="flex gap-2 mt-2 justify-end">
                        <a href="{{ route('properties.show', $property) }}" class="btn text-sm">Открыть</a>
                        <a href="{{ route('properties.edit', $property) }}" class="btn text-sm">Изменить</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-8">{{ $properties->links() }}</div>
@else
    <div class="card p-12 text-center text-gray-600">
        Объекты не найдены. <a href="{{ route('properties.create') }}" class="text-brand-700 hover:underline">Создать объявление</a>
    </div>
@endif
@endsection
