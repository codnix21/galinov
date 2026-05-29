@extends('layouts.app')

@section('title', 'Купить онлайн — ' . $property->nazvanie)

@section('content')
<div class="max-w-3xl mx-auto">
    <a href="{{ route('properties.show', $property) }}" class="text-sm hover:underline">← К объекту</a>
    <h1 class="text-3xl font-bold mt-4 mb-2">Покупка без риэлтора</h1>
    <p class="text-gray-600 mb-8">Договор заполнится автоматически по данным объявления. После подтверждения — тестовая оплата и документы.</p>

    <div class="card p-6 mb-6">
        <h2 class="text-xl font-bold mb-2">{{ $property->nazvanie }}</h2>
        <p class="text-2xl font-bold text-green-700 mb-2">{{ number_format((float)$property->tsena, 0, ',', ' ') }} ₽</p>
        <p class="text-sm text-gray-600">{{ $property->gorod }}, {{ $property->adres_ulitsy }}</p>
        <div class="mt-4">
            <a href="{{ $mortgageUrl }}" class="btn text-sm">Ипотека и стоимость кредита</a>
        </div>
    </div>

    <form method="POST" action="{{ route('purchase.store', $property) }}" class="card p-6 space-y-4">
        @csrf
        <p class="text-sm text-gray-700">Покупатель: <strong>{{ Auth::user()->name }}</strong> ({{ Auth::user()->email }})</p>
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="confirm_terms" value="1" required class="mt-1">
            <span>Согласен с автоматическим формированием договора, тестовой оплатой и проверкой документов продавца перед регистрацией сделки.</span>
        </label>
        <button type="submit" class="btn-primary w-full">Оформить сделку → оплата</button>
    </form>
</div>
@endsection
