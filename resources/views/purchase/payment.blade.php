@extends('layouts.app')

@section('title', 'Оплата')

@section('content')
<div class="max-w-lg mx-auto">
    <h1 class="text-2xl sm:text-3xl font-bold mb-2">Оплата сделки</h1>
    <p class="text-gray-600 text-sm mb-6">Подтвердите оплату и перейдите к завершению сделки.</p>

    <div class="card p-5 mb-6">
        <p class="text-sm text-gray-600">Договор №{{ $contract->id }}</p>
        <p class="text-lg font-bold">{{ $contract->property?->nazvanie }}</p>
        <p class="text-2xl sm:text-3xl font-bold text-green-700 mt-2">{{ number_format((float)$contract->tsena, 0, ',', ' ') }} ₽</p>
    </div>

    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">{{ session('success') }}</div>
    @endif

    @if($contract->isPaid())
        <a href="{{ route('purchase.complete', $contract) }}" class="btn-primary w-full text-center block">Квитанция →</a>
    @elseif($robokassaEnabled)
        <form method="POST" action="{{ route('purchase.pay.robokassa', $contract) }}" class="mb-6">
            @csrf
            <label class="flex items-start gap-2 text-sm mb-4">
                <input type="checkbox" name="accept_offer" value="1" required class="mt-1">
                <span>Согласен с условиями оплаты и офертой</span>
            </label>
            <button type="submit" class="btn-primary w-full py-3 text-base">
                Оплатить {{ number_format((float)$contract->tsena, 0, ',', ' ') }} ₽
            </button>
        </form>

        @if($testGatewayEnabled)
            <details class="card p-5">
                <summary class="cursor-pointer font-medium text-sm text-slate-700">Тестовый шлюз (без Robokassa)</summary>
                <div class="mt-4 pt-4 border-t border-slate-100">
                    @include('purchase.partials.test-payment-form', ['contract' => $contract])
                </div>
            </details>
        @endif
    @else
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 mb-4">
            Robokassa не настроена. Добавьте ROBOKASSA_LOGIN, ROBOKASSA_PASSWORD1 и ROBOKASSA_PASSWORD2 в .env на сервере.
        </div>
        @if($testGatewayEnabled)
            @include('purchase.partials.test-payment-form', ['contract' => $contract])
        @endif
    @endif
</div>
@endsection
