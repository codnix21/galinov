@extends('layouts.app')

@section('title', 'Сделка оформлена')

@section('content')
<div class="max-w-2xl mx-auto text-center" style="text-align: center;">
    <div class="text-5xl mb-4">✓</div>
    <h1 class="text-3xl font-bold mb-2">Оплата получена</h1>
    <p class="text-gray-600 mb-8">Документы по сделке доступны для скачивания. Договор ожидает подтверждения сторонами.</p>

    <div class="card p-6 mb-6 text-left">
        <p><strong>Объект:</strong> {{ $contract->property?->nazvanie }}</p>
        <p><strong>Сумма:</strong> {{ number_format((float)$contract->tsena, 0, ',', ' ') }} ₽</p>
        <p><strong>Оплата:</strong> {{ $contract->oplata_at?->format('d.m.Y H:i') }}</p>
        @if($contract->oplata_tranzaktsiya)
            <p><strong>Транзакция:</strong> {{ $contract->oplata_tranzaktsiya }}</p>
            <p><strong>Способ:</strong> {{ $contract->oplata_metod === 'sbp' ? 'СБП' : 'Карта' }}</p>
            <p><strong>Сумма:</strong> {{ number_format((float)($contract->oplata_summa ?? $contract->tsena), 0, ',', ' ') }} ₽</p>
        @endif
    </div>

    @if(!empty($canSignEcp) || !($ecpFullySigned ?? false))
        <div class="text-left mb-6">
            @include('contracts.partials.ecp-signatures', [
                'contract' => $contract,
                'ecpStatuses' => $ecpStatuses,
                'canSignEcp' => $canSignEcp ?? false,
                'ecpFullySigned' => $ecpFullySigned ?? false,
                'viewerPartyRole' => 'buyer',
            ])
        </div>
    @endif

    <div class="flex flex-col gap-3">
        @if($ecpFullySigned ?? false)
            <a href="{{ route('contracts.pdf', $contract) }}" class="btn-primary" target="_blank">Скачать договор (PDF с подписями УКЭП)</a>
        @endif
        <a href="{{ route('contracts.show', $contract) }}" class="btn">Карточка договора</a>
        @if($contract->property)
            <a href="{{ route('properties.show', $contract->property) }}" class="btn">К объявлению</a>
        @endif
    </div>
</div>
@endsection
