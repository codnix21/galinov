@extends('layouts.app')

@section('title', 'Отчёт по объекту — ' . $property->nazvanie)

@section('content')
@php
    $p = $property;
@endphp

<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <a href="{{ route('properties.show', $p) }}" class="text-sm text-brand-700 hover:underline">← К объявлению</a>
    </div>

    <div class="card p-6 sm:p-8 border-brand-100 bg-gradient-to-br from-white to-brand-50/30">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-700 mb-1">Полный отчёт по объекту</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 break-words">{{ $p->nazvanie }}</h1>
                <p class="text-slate-600 mt-1">№ {{ $p->id }} · {{ $p->type_name }} · {{ $p->operation_name }}</p>
            </div>
            <div class="text-right shrink-0">
                <p class="text-2xl font-bold text-brand-800">{{ number_format((float) $p->tsena, 0, ',', ' ') }} ₽</p>
                <span class="badge mt-2">{{ $statusLabel }}</span>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-xl bg-white/80 border border-slate-200 p-3 text-center">
                <p class="text-2xl font-bold text-brand-700">{{ $docsPercent }}%</p>
                <p class="text-xs text-slate-600 mt-1">Документы проверены</p>
            </div>
            <div class="rounded-xl bg-white/80 border border-slate-200 p-3 text-center">
                <p class="text-2xl font-bold text-slate-800">{{ $contracts->count() }}</p>
                <p class="text-xs text-slate-600 mt-1">Сделок / договоров</p>
            </div>
            <div class="rounded-xl bg-white/80 border border-slate-200 p-3 text-center">
                <p class="text-2xl font-bold text-slate-800">{{ $inquiriesCount }}</p>
                <p class="text-xs text-slate-600 mt-1">Заявок на объект</p>
            </div>
            <div class="rounded-xl bg-white/80 border border-slate-200 p-3 text-center">
                <p class="text-2xl font-bold {{ $profilePassport ? 'text-green-700' : 'text-amber-700' }}">{{ $profilePassport ? '✓' : '—' }}</p>
                <p class="text-xs text-slate-600 mt-1">Паспорт продавца</p>
            </div>
        </div>
    </div>

    <div class="card p-6">
        <h2 class="text-lg font-bold mb-4">Паспорт объекта</h2>
        <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div><dt class="text-slate-500">Город</dt><dd class="font-medium">{{ $p->gorod ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Адрес</dt><dd class="font-medium">{{ $p->adres_ulitsy ?? '—' }}</dd></div>
            @if($p->kadastrovy_nomer)
                <div class="sm:col-span-2"><dt class="text-slate-500">Кадастровый номер</dt><dd class="font-medium font-mono">{{ $p->kadastrovy_nomer }}</dd></div>
            @endif
            @if($p->ploshchad)<div><dt class="text-slate-500">Площадь</dt><dd class="font-medium">{{ $p->ploshchad }} м²</dd></div>@endif
            @if($p->komnaty)<div><dt class="text-slate-500">Комнат</dt><dd class="font-medium">{{ $p->komnaty }}</dd></div>@endif
            @if($p->etazh)<div><dt class="text-slate-500">Этаж</dt><dd class="font-medium">{{ $p->etazh }}{{ $p->vsego_etazhey ? '/' . $p->vsego_etazhey : '' }}</dd></div>@endif
            <div><dt class="text-slate-500">Размещено</dt><dd class="font-medium">{{ $p->sozdano_at?->format('d.m.Y') ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Обновлено</dt><dd class="font-medium">{{ $p->obnovleno_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
        </dl>
        @if($p->opisanie)
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-xs text-slate-500 mb-1">Описание</p>
                <p class="text-sm text-slate-800 whitespace-pre-line">{{ $p->opisanie }}</p>
            </div>
        @endif
        <div class="mt-4 flex flex-wrap gap-2">
            @if($panoramaUrl)
                <a href="{{ $panoramaUrl }}" target="_blank" rel="noopener" class="btn text-sm">Панорама района</a>
            @endif
            @if($rosreestrUrl)
                <a href="{{ $rosreestrUrl }}" target="_blank" rel="noopener" class="btn text-sm">Карта Росреестра</a>
            @endif
            <a href="{{ route('pages.mortgage-calculator', ['price' => (int) $p->tsena, 'property_id' => $p->id]) }}" class="btn text-sm">Расчёт ипотеки</a>
        </div>
    </div>

    <div class="card p-6">
        <h2 class="text-lg font-bold mb-1">Проверка документов</h2>
        <p class="text-sm text-slate-600 mb-4">{{ $requirementsSummary }}</p>
        <div class="h-2 rounded-full bg-slate-100 mb-4 overflow-hidden">
            <div class="h-full bg-brand-600 rounded-full" style="width: {{ $docsPercent }}%"></div>
        </div>
        <ul class="space-y-2">
            @foreach($requiredDocs as $tip)
                @php
                    $ok = in_array($tip, $docStatus['verified'], true);
                    $pending = in_array($tip, $docStatus['pending'], true);
                    $rejected = in_array($tip, $docStatus['rejected'], true);
                @endphp
                <li class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm {{ $ok ? 'border-green-200 bg-green-50/50' : ($rejected ? 'border-red-200 bg-red-50/40' : 'border-slate-200') }}">
                    <span>{{ $docLabels[$tip] ?? $tip }}</span>
                    <span class="font-medium shrink-0 {{ $ok ? 'text-green-700' : ($rejected ? 'text-red-700' : ($pending ? 'text-amber-700' : 'text-slate-500')) }}">
                        @if($ok) Проверен
                        @elseif($rejected) Отклонён
                        @elseif($pending) На проверке
                        @else Не загружен
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
        @if($isOwnerOrStaff ?? false)
            <a href="{{ route('properties.documents', $p) }}" class="btn-primary inline-block mt-4 text-sm">Управление документами</a>
        @endif
    </div>

    <div class="card p-6">
        <h2 class="text-lg font-bold mb-4">История сделок и договоров</h2>
        @if($contracts->isEmpty())
            <p class="text-sm text-slate-600">По этому объекту договоров в системе пока нет. После онлайн-покупки или экспресс-сделки здесь появится история.</p>
        @else
            <div class="space-y-3">
                @foreach($contracts as $contract)
                    <div class="rounded-xl border border-slate-200 p-4 text-sm">
                        <div class="flex flex-wrap justify-between gap-2 mb-2">
                            <span class="font-semibold">Договор №{{ $contract->id }}</span>
                            <span class="badge">{{ $contract->status_name }}</span>
                        </div>
                        <p class="text-slate-600">{{ $contract->type_name }} · {{ number_format((float) $contract->tsena, 0, ',', ' ') }} ₽</p>
                        @if($contract->data_nachala)
                            <p class="text-slate-500 text-xs mt-1">с {{ $contract->data_nachala->format('d.m.Y') }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600">
                            @if($contract->ecp_podpis_vladelets_at)
                                <span class="text-green-700">✓ Продавец (УКЭП)</span>
                            @endif
                            @if($contract->ecp_podpis_pokupatel_at)
                                <span class="text-green-700">✓ Покупатель (УКЭП)</span>
                            @endif
                            @if($contract->isPaid())
                                <span class="text-green-700">✓ Оплачено</span>
                            @endif
                        </div>
                        @auth
                            @php
                                $canOpenContract = Auth::user()->isStaff()
                                    || \App\Support\ContractApproval::userIsParty($contract, Auth::user());
                            @endphp
                            @if($canOpenContract)
                                <a href="{{ route('contracts.show', $contract) }}" class="inline-block mt-2 text-brand-700 underline text-xs">Открыть договор →</a>
                            @else
                                <p class="text-xs text-slate-500 mt-2">Детали договора доступны только участникам сделки.</p>
                            @endif
                        @endauth
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if($canSeeFullHistory && $history->isNotEmpty())
        @include('partials.property-zhurnal', ['property' => $p, 'istoriyaZhurnala' => $history])
    @elseif(!$canSeeFullHistory)
        <div class="card p-6 text-sm text-slate-600">
            <h2 class="text-lg font-bold text-slate-900 mb-2">Журнал изменений</h2>
            <p>Подробная история правок объявления и договоров доступна владельцу и сотрудникам платформы.</p>
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('properties.show', $p) }}" class="btn">К карточке объявления</a>
        @if(($statusCode ?? '') === 'active' && Auth::check() && (int) Auth::id() !== (int) ($p->polzovatel_id ?? 0))
            <a href="{{ route('purchase.buy', $p) }}" class="btn-primary">Купить онлайн</a>
        @endif
    </div>
</div>
@endsection
