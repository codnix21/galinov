@extends('layouts.app')

@section('title', $collection->nazvanie)

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8 text-center">
        <p class="text-sm text-gray-500 uppercase tracking-wide">Подборка от агентства</p>
        <h1 class="text-4xl font-bold mt-2">{{ $collection->nazvanie }}</h1>
        @if($collection->kommentariy)
            <p class="text-gray-600 mt-4 max-w-2xl mx-auto">{{ $collection->kommentariy }}</p>
        @endif
        @if($collection->realtor)
            <p class="text-sm text-gray-500 mt-2">
                Риэлтор: {{ trim($collection->realtor->familia.' '.$collection->realtor->imya) }}
                @if($collection->realtor->telefon) · {{ $collection->realtor->telefon }} @endif
            </p>
        @endif
    </div>

    @if($collection->items->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($collection->items as $item)
                @php $p = $item->property; @endphp
                <a href="{{ route('properties.show', $p) }}" class="card p-0 overflow-hidden block hover:shadow-lg transition-shadow">
                    <div class="h-48 bg-gray-100">
                        @if($p->images->count())
                            <img src="{{ $p->images->first()->public_url }}" class="w-full h-full object-cover" alt="">
                        @endif
                    </div>
                    <div class="p-5">
                        <span class="badge">{{ $p->type_name }}</span>
                        <h2 class="font-bold text-lg mt-2">{{ $p->nazvanie }}</h2>
                        <p class="text-sm text-gray-600">{{ $p->gorod }} · {{ $p->operation_name }}</p>
                        <p class="text-xl font-bold mt-3">{{ number_format($p->tsena, 0, ',', ' ') }} ₽</p>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="card p-12 text-center text-gray-500">В подборке пока нет объектов</div>
    @endif
</div>
@endsection
