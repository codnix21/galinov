@extends('layouts.app')

@section('title', 'Заявки по объектам')

@section('content')
@include('partials.realtor-nav')

<h1 class="text-3xl font-bold mb-6">Заявки клиентов</h1>

@forelse($inquiries as $inq)
    <div class="card p-5 mb-4 {{ $inq->status === 'new' ? 'border-amber-300 border-2' : '' }}">
        <div class="flex flex-wrap justify-between gap-2">
            <div>
                <p class="font-bold">
                    <a href="{{ route('properties.show', $inq->property) }}" class="hover:underline">{{ $inq->property?->nazvanie }}</a>
                </p>
                <p class="text-sm text-gray-600">{{ $inq->imya }} · {{ $inq->telefon ?? $inq->email ?? '—' }}</p>
                @if($inq->kommentariy)<p class="text-sm mt-2">{{ $inq->kommentariy }}</p>@endif
                <p class="text-xs text-gray-500 mt-1">{{ $inq->sozdano_at?->format('d.m.Y H:i') }}</p>
            </div>
            @if($inq->status === 'new')
                <form method="POST" action="{{ route('realtor.inquiries.process', $inq) }}">
                    @csrf
                    <button type="submit" class="btn-primary text-sm">Обработано</button>
                </form>
            @else
                <span class="badge bg-green-100 text-green-800">Обработано</span>
            @endif
        </div>
    </div>
@empty
    <div class="card p-8 text-center text-gray-600">Новых заявок нет.</div>
@endforelse

<div class="mt-6">{{ $inquiries->links() }}</div>
@endsection
