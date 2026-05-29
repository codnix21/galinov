@extends('layouts.app')

@section('title', 'Карта объявлений')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-4xl font-bold mb-2">Карта объявлений</h1>
        <p class="text-gray-600">
            На карте: {{ $markers->count() }} из {{ $allFiltered }} (только с координатами)
        </p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('properties.index', request()->query()) }}" class="btn">← Список</a>
    </div>
</div>

<div class="card p-6 mb-6 catalog-filters">
    <form method="GET" action="{{ route('properties.map') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <select name="city_id" class="form-input">
            <option value="">Все города</option>
            @foreach($cities as $city)
                <option value="{{ $city->id }}" {{ (string) request('city_id') === (string) $city->id ? 'selected' : '' }}>{{ $city->nazvanie }}</option>
            @endforeach
        </select>
        <select name="type" class="form-input">
            <option value="">Все типы</option>
            @foreach(\App\Models\Property::tipNazvaniya() as $value => $label)
                <option value="{{ $value }}" {{ request('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="operation" class="form-input">
            <option value="">Все операции</option>
            <option value="sale" {{ request('operation') == 'sale' ? 'selected' : '' }}>Продажа</option>
            <option value="rent" {{ request('operation') == 'rent' ? 'selected' : '' }}>Аренда</option>
        </select>
        <button type="submit" class="btn-primary">Применить</button>
    </form>
</div>

@if(!$mapApiKey)
    <div class="card p-8 text-center text-amber-800">
        Укажите YANDEX_MAPS_API_KEY (или YANDEX_GEOCODER_API_KEY) в .env для отображения карты.
    </div>
@else
    <div id="properties-map" class="rounded-2xl overflow-hidden border border-slate-200 shadow-sm" style="height: 560px; width: 100%;"></div>
    <script>
        window.galinovMapConfig = {
            apiKey: @json($mapApiKey),
            markers: @json($markers),
            defaultCenter: [55.751244, 37.618423],
            defaultZoom: 10
        };
    </script>
    @vite(['resources/js/properties-map.js'])
@endif
@endsection
