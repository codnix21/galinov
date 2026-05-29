{{-- Просмотр договора и действия по статусу. --}}
@extends('layouts.app')

@section('title', 'Договор №' . $contract->id)

@section('content')
@php use App\Support\ContractApproval; @endphp
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold mb-2">Договор №{{ $contract->id }}</h1>
                <p class="text-gray-600">
                    @if(($contract->tip ?? $contract->type) === 'rent')
                        Договор аренды недвижимости
                    @else
                        Договор купли-продажи недвижимости
                    @endif
                </p>
            </div>
            @php
                $statusBadge = match(true) {
                    $ecpFullySigned ?? false, ($contract->status ?? '') === 'active' => 'bg-green-100 text-green-800',
                    ($contract->status ?? '') === 'pending' => 'bg-yellow-100 text-yellow-800',
                    ($contract->status ?? '') === 'cancelled' => 'bg-red-100 text-red-800',
                    default => 'bg-gray-100 text-gray-800',
                };
            @endphp
            <span class="badge {{ $statusBadge }}">
                {{ $displayStatusName ?? $contract->status_name }}
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if(isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    @php
        $helpPoints = $ecpFullySigned ?? false
            ? [
                'Договор подписан УКЭП всеми сторонами — скачайте PDF с отметками.',
                'Бумажная встреча для подписи не нужна; скан в архив — по желанию риэлтора.',
            ]
            : [
                'Собственник и риэлтор — автоподпись УКЭП при создании договора.',
                'Покупатель нажимает «Подписать договор (ЭЦП)».',
                'После всех подписей скачайте PDF.',
            ];
    @endphp
    @include('partials.help-hint', ['title' => 'Как подписывается договор', 'points' => $helpPoints])

    <div class="card p-8 mb-6">
        <h2 class="text-2xl font-bold mb-6">Информация о договоре</h2>
        
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <span class="text-sm text-gray-600 block mb-1">Тип договора</span>
                <span class="font-medium text-lg">{{ $contract->type_name }}</span>
            </div>
            <div>
                <span class="text-sm text-gray-600 block mb-1">Статус</span>
                <span class="font-medium text-lg">{{ $displayStatusName ?? $contract->status_name }}</span>
            </div>
            <div>
                <span class="text-sm text-gray-600 block mb-1">{{ ($contract->tip ?? $contract->type) === 'rent' ? 'Арендная плата (мес.)' : 'Цена' }}</span>
                <span class="font-medium text-lg">{{ number_format($contract->tsena, 0, ',', ' ') }} ₽</span>
            </div>
            <div>
                <span class="text-sm text-gray-600 block mb-1">Дата начала</span>
                <span class="font-medium text-lg">{{ $contract->data_nachala->format('d.m.Y') }}</span>
            </div>
            @if(($contract->tip ?? $contract->type) === 'rent' && $contract->data_okonchaniya)
            <div>
                <span class="text-sm text-gray-600 block mb-1">Дата окончания аренды</span>
                <span class="font-medium text-lg">{{ $contract->data_okonchaniya->format('d.m.Y') }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="card p-8 mb-6">
        <h2 class="text-2xl font-bold mb-6">Объект недвижимости</h2>
        
        <div class="mb-4">
            <h3 class="text-xl font-bold mb-2">
                <a href="{{ route('properties.show', $contract->property) }}" class="text-blue-600 hover:underline">
                    {{ $contract->property->nazvanie }}
                </a>
            </h3>
            <p class="text-gray-600 mb-3">{{ ($contract->property->gorod ?? '') . ', ' . ($contract->property->adres_ulitsy ?? '') }}</p>
            <div class="flex items-center gap-6 text-sm text-gray-600">
                @if($contract->property->ploshchad)
                    <span>Площадь: <span class="font-medium">{{ $contract->property->ploshchad }} м²</span></span>
                @endif
                @if($contract->property->komnaty)
                    <span>Комнат: <span class="font-medium">{{ $contract->property->komnaty }}</span></span>
                @endif
                <span>Цена объекта: <span class="font-medium">{{ number_format($contract->property->tsena, 0, ',', ' ') }} ₽</span></span>
            </div>
        </div>
    </div>

    <div class="card p-6 mb-6">
        <h2 class="text-2xl font-bold mb-4">Стороны сделки</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <h3 class="text-lg font-bold mb-2">Сторона 1: владелец</h3>
                @if($contract->owner)
                    <p><strong>Имя:</strong> {{ trim($contract->owner->familia . ' ' . $contract->owner->imya . ' ' . $contract->owner->otchestvo) }}</p>
                    <p><strong>Email:</strong> {{ $contract->owner->email_polzovatela }}</p>
                    @if($contract->owner->telefon)
                        <p><strong>Телефон:</strong> {{ $contract->owner->telefon }}</p>
                    @endif
                @else
                    <p class="text-gray-500">Не указан</p>
                @endif
            </div>
            <div>
                <h3 class="text-lg font-bold mb-2">Сторона 2: покупатель</h3>
                @if($contract->buyer)
                    <p><strong>Имя:</strong> {{ trim($contract->buyer->familia . ' ' . $contract->buyer->imya . ' ' . $contract->buyer->otchestvo) }}</p>
                    <p><strong>Email:</strong> {{ $contract->buyer->email_polzovatela }}</p>
                    @if($contract->buyer->telefon)
                        <p><strong>Телефон:</strong> {{ $contract->buyer->telefon }}</p>
                    @endif
                @else
                    <p class="text-gray-500">Не указан</p>
                @endif
            </div>
            <div>
                <h3 class="text-lg font-bold mb-2">Риэлтор сделки</h3>
                @if($contract->realtor)
                    <p><strong>Имя:</strong> {{ trim($contract->realtor->familia . ' ' . $contract->realtor->imya . ' ' . $contract->realtor->otchestvo) }}</p>
                    <p><strong>Email:</strong> {{ $contract->realtor->email_polzovatela }}</p>
                    @if($contract->realtor->telefon)
                        <p><strong>Телефон:</strong> {{ $contract->realtor->telefon }}</p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    @if($ecpFullySigned ?? false)
        <div class="card p-6 mb-6 border-green-200 bg-green-50/80">
            <h3 class="text-lg font-bold mb-2 text-green-900">Согласование сторон</h3>
            <p class="text-sm text-green-800">
                Все стороны подписали договор УКЭП — отдельное ручное подтверждение не требуется.
                @if(($contract->status ?? '') === 'active')
                    Статус сделки: <strong>активен</strong>.
                @endif
            </p>
        </div>
    @elseif($contract->status === 'pending')
        @php $approvalSummary = ContractApproval::pendingSummary($contract); @endphp
        <div class="card p-6 mb-6 border-slate-200 bg-slate-50/80">
            <h3 class="text-lg font-bold mb-3">Согласование условий (до подписи УКЭП)</h3>
            <p class="text-xs text-gray-500 mb-3">После подписи УКЭП всеми сторонами этот шаг засчитывается автоматически.</p>
            <ul class="text-sm space-y-2 text-gray-700">
                <li>
                    Владелец:
                    @if(!ContractApproval::needsOwnerApproval($contract))
                        <span class="text-gray-500">не требуется (создал клиент)</span>
                    @elseif(ContractApproval::isOwnerApproved($contract))
                        <span class="text-green-700 font-medium">подтверждён</span>
                    @else
                        <span class="text-amber-700 font-medium">ожидает</span>
                    @endif
                </li>
                <li>
                    Покупатель:
                    @if(!ContractApproval::needsBuyerApproval($contract))
                        <span class="text-gray-500">не требуется (создал клиент)</span>
                    @elseif(ContractApproval::isBuyerApproved($contract))
                        <span class="text-green-700 font-medium">подтверждён</span>
                    @else
                        <span class="text-amber-700 font-medium">ожидает</span>
                    @endif
                </li>
                <li>
                    Риэлтор:
                    @if(!ContractApproval::needsRealtorApproval($contract))
                        <span class="text-gray-500">не требуется (создал риэлтор)</span>
                    @elseif(ContractApproval::isRealtorApproved($contract))
                        <span class="text-green-700 font-medium">подтверждён</span>
                    @else
                        <span class="text-amber-700 font-medium">ожидает</span>
                    @endif
                </li>
            </ul>
            @if($approvalSummary !== '—')
                <p class="mt-3 text-sm text-gray-600">Ожидаем подтверждения: {{ $approvalSummary }}.</p>
            @endif
        </div>
    @endif

    @if($contract->primechaniya)
        <div class="card p-6 mb-6">
            <h3 class="text-xl font-bold mb-4">Примечания</h3>
            <p class="text-gray-700">{{ $contract->primechaniya }}</p>
        </div>
    @endif

    @include('contracts.partials.ecp-signatures', compact('contract', 'ecpStatuses', 'canSignEcp', 'ecpFullySigned', 'viewerPartyRole'))

    @php
        $showPaperArchive = (bool) $contract->skan_dogovora
            || (($ecpFullySigned ?? false) && (Auth::user()->isRealtor() || Auth::user()->isAdmin()));
    @endphp
    @if($showPaperArchive)
        @include('contracts.partials.signed-document', [
            'contract' => $contract,
            'ecpFullySigned' => $ecpFullySigned ?? false,
        ])
    @endif

    @if($contract->status === 'pending' && !($ecpFullySigned ?? false))
        @php $canModeratePending = ContractApproval::userCanApprove(Auth::user(), $contract); @endphp
        @if($canModeratePending)
            <div class="card p-6 mb-6 border-amber-200 bg-amber-50/50">
                <h3 class="text-lg font-bold mb-3">Требуется ваше решение</h3>
                <p class="text-sm text-gray-600 mb-4">Подтвердите или отклоните условия договора от своей стороны.</p>
                <div class="flex flex-wrap gap-3">
                    <form action="{{ route('contracts.approve', $contract) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn-primary" onclick="return confirm('Подтвердить договор?')">Подтвердить</button>
                    </form>
                    <form action="{{ route('contracts.reject', $contract) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn bg-red-600 hover:bg-red-700 text-white" onclick="return confirm('Отклонить договор?')">Отклонить</button>
                    </form>
                </div>
            </div>
        @endif
    @endif

    <div class="flex flex-wrap items-center justify-end gap-4">
        @if(($contract->tip ?? $contract->type) === 'rent' && $contract->status === 'active')
            <form action="{{ route('contracts.complete', $contract) }}" method="POST" class="inline" onsubmit="return confirm('Завершить аренду? Объект снова появится в каталоге как активное объявление.');">
                @csrf
                <button type="submit" class="btn-primary">Завершить аренду</button>
            </form>
        @endif
        <a href="{{ route('contracts.index') }}" class="btn">
            Назад к списку
        </a>
    </div>
</div>

@include('partials.status-version-history', ['versions' => $statusVersions ?? []])
@endsection


