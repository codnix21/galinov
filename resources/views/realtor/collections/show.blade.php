@extends('layouts.app')

@section('title', $collection->nazvanie)

@section('content')
@include('partials.realtor-nav')

<div class="mb-6">
    <a href="{{ route('realtor.collections.index') }}" class="text-sm text-brand-700 hover:underline">← Подборки</a>
    <h1 class="text-3xl font-bold mt-2">{{ $collection->nazvanie }}</h1>
    @if($collection->kommentariy)<p class="text-gray-600">{{ $collection->kommentariy }}</p>@endif
</div>

<div class="card p-6 mb-8 bg-brand-50 border-brand-100">
    <p class="text-sm font-medium text-brand-900">Ссылка для клиента</p>
    <div class="flex flex-wrap gap-2 mt-2 items-center">
        <code class="text-sm break-all flex-1">{{ $collection->publicUrl() }}</code>
        <a href="{{ $collection->publicUrl() }}" target="_blank" class="btn text-sm">Открыть</a>
        <a href="{{ route('realtor.collections.pdf', $collection) }}" class="btn-primary text-sm">Скачать PDF</a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    @foreach($collection->items as $item)
        @php $p = $item->property; @endphp
        <div class="card p-4 relative">
            <form method="POST" action="{{ route('realtor.collections.remove-property', [$collection, $p]) }}" class="absolute top-2 right-2">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-500 text-sm" title="Убрать">×</button>
            </form>
            @if($p->images->count())
                <img src="{{ $p->images->first()->public_url }}" class="w-full h-32 object-cover rounded-lg mb-3" alt="">
            @endif
            <h3 class="font-bold">{{ $p->nazvanie }}</h3>
            <p class="text-sm text-gray-600">{{ $p->gorod }}</p>
            <p class="font-bold mt-2">{{ number_format($p->tsena, 0, ',', ' ') }} ₽</p>
            <a href="{{ route('properties.show', $p) }}" class="text-sm text-brand-700 mt-2 inline-block">Карточка →</a>
        </div>
    @endforeach
</div>

@if($addable->count() > 0)
<div class="card p-6">
    <h2 class="font-bold mb-4">Добавить объект</h2>
    <form method="POST" action="{{ route('realtor.collections.add-property', $collection) }}" class="flex flex-wrap gap-3">
        @csrf
        <select name="nedvizhimost_id" class="form-input flex-1 min-w-[200px]" data-placeholder="Поиск объекта…">
            @foreach($addable as $p)
                @php
                    $op = ($p->operatsiya ?? '') === 'rent' ? 'Аренда' : 'Продажа';
                    $city = $p->gorod ?? '';
                @endphp
                <option value="{{ $p->id }}">
                    #{{ $p->id }} — {{ Str::limit($p->nazvanie, 50) }} · {{ $op }}@if($city) · {{ $city }}@endif
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn-primary">Добавить</button>
    </form>
</div>
@endif

<form method="POST" action="{{ route('realtor.collections.destroy', $collection) }}" class="mt-8 js-confirm-action" data-confirm="Удалить подборку?">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn text-red-700">Удалить подборку</button>
</form>
@endsection
