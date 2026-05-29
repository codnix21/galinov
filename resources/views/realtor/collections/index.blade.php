@extends('layouts.app')

@section('title', 'Подборки')

@section('content')
@include('partials.realtor-nav')

<div class="mb-6 flex flex-wrap justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold">Подборки</h1>
        <p class="text-gray-600">Ссылки для отправки клиентам</p>
    </div>
    <a href="{{ route('realtor.collections.create') }}" class="btn-primary">+ Создать подборку</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse($collections as $col)
        <div class="card p-6">
            <h3 class="font-bold text-lg">{{ $col->nazvanie }}</h3>
            <p class="text-sm text-gray-600 mt-1">
                {{ $col->items->count() }} объектов
                @if($col->client) · {{ trim($col->client->familia.' '.$col->client->imya) }} @endif
            </p>
            <p class="text-xs text-gray-500 mt-2 break-all">{{ $col->publicUrl() }}</p>
            <div class="flex gap-2 mt-4">
                <a href="{{ route('realtor.collections.show', $col) }}" class="btn text-sm">Открыть</a>
                <a href="{{ $col->publicUrl() }}" target="_blank" class="btn text-sm">Ссылка для клиента</a>
            </div>
        </div>
    @empty
        <div class="card p-12 text-center text-gray-500 md:col-span-2">
            Подборок пока нет. <a href="{{ route('realtor.collections.create') }}" class="text-brand-700">Создать</a>
            или из <a href="{{ route('favorites.index') }}" class="text-brand-700">избранного</a>.
        </div>
    @endforelse
</div>
<div class="mt-8">{{ $collections->links() }}</div>
@endsection
