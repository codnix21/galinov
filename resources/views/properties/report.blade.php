@extends('layouts.app')

@section('title', 'Отчёт по объекту — ' . $property->nazvanie)

@section('content')
@php
    $p = $property;
    $st = $statusCode ?? ($p->status_obyavleniya ?? $p->status);
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
                <p class="text-2xl font-bold text-brand-800">
                    {{ number_format((float) $p->tsena, 0, ',', ' ') }} ₽@if($isRent ?? false)<span class="text-base font-semibold text-slate-600">/мес</span>@endif
                </p>
                <span class="badge mt-2">{{ $statusLabel }}</span>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="rounded-xl bg-white/80 border border-slate-200 p-3 text-center">
                <p class="text-2xl font-bold text-brand-700">{{ $docsPercent }}%</p>
                <p class="text-xs text-slate-600 mt-1">Документы</p>
            </div>
            <div class="rounded-xl bg-white/80 border border-slate-200 p-3 text-center">
                <p class="text-2xl font-bold text-slate-800">{{ $contracts->count() }}</p>
                <p class="text-xs text-slate-600 mt-1">Договоров</p>
            </div>
            <div class="rounded-xl bg-white/80 border border-slate-200 p-3 text-center">
                <p class="text-2xl font-bold text-slate-800">{{ $inquiriesCount }}</p>
                <p class="text-xs text-slate-600 mt-1">Заявок</p>
            </div>
            <div class="rounded-xl bg-white/80 border border-slate-200 p-3 text-center">
                <p class="text-2xl font-bold text-slate-800">{{ $infoRequestsCount ?? 0 }}</p>
                <p class="text-xs text-slate-600 mt-1">Запросов инфо</p>
            </div>
            <div class="rounded-xl bg-white/80 border border-slate-200 p-3 text-center">
                <p class="text-2xl font-bold text-slate-800">{{ $favoritesCount ?? 0 }}</p>
                <p class="text-xs text-slate-600 mt-1">В избранном</p>
            </div>
            <div class="rounded-xl bg-white/80 border border-slate-200 p-3 text-center">
                <p class="text-2xl font-bold {{ $profilePassport ? 'text-green-700' : 'text-amber-700' }}">{{ $profilePassport ? '✓' : '—' }}</p>
                <p class="text-xs text-slate-600 mt-1">Паспорт продавца</p>
            </div>
        </div>
    </div>

    @if(($isOwnerOrStaff ?? false) && $canManage)
        @include('properties.partials.listing-stepper', [
            'property' => $p,
            'docsReady' => $docsReady ?? false,
            'st' => $st,
            'profileVerifiedTips' => $profileVerifiedTips ?? [],
            'canPublishToModeration' => $canPublishToModeration ?? false,
            'canManage' => true,
        ])
    @endif

    @if($st === 'draft' && !empty($p->prichina_otkaza_mod))
        <div class="card p-4 border-red-200 bg-red-50 text-sm text-red-900">
            <p class="font-semibold mb-1">Публикация отклонена модератором</p>
            <p class="whitespace-pre-line">{{ $p->prichina_otkaza_mod }}</p>
        </div>
    @endif

    @if(($imagesCount ?? 0) > 0)
        <div class="card p-6">
            <h2 class="text-lg font-bold mb-3">Фотографии ({{ $imagesCount }})</h2>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                @foreach($p->images->take(12) as $image)
                    <a href="{{ route('properties.show', $p) }}" class="aspect-square rounded-lg overflow-hidden border border-slate-200 hover:opacity-90">
                        <img src="{{ $image->public_url }}" alt="" class="w-full h-full object-cover">
                    </a>
                @endforeach
            </div>
            @if($imagesCount > 12)
                <p class="text-xs text-slate-500 mt-2">Показаны первые 12 из {{ $imagesCount }} — полная галерея на карточке объявления.</p>
            @endif
        </div>
    @endif

    @if($isOwnerOrStaff ?? false)
        <div class="card p-6">
            <h2 class="text-lg font-bold mb-4">Участники и размещение</h2>
            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                @if($p->user)
                    <div>
                        <dt class="text-slate-500">Владелец объявления</dt>
                        <dd class="font-medium">{{ trim($p->user->familia.' '.$p->user->imya.' '.$p->user->otchestvo) }}</dd>
                        @if($p->user->telefon)
                            <dd class="text-slate-600 text-xs mt-0.5">{{ $p->user->telefon }}</dd>
                        @endif
                        @if($p->user->email_polzovatela)
                            <dd class="text-slate-600 text-xs">{{ $p->user->email_polzovatela }}</dd>
                        @endif
                    </div>
                @endif
                @if($p->realtor && (int) $p->realtor->id !== (int) ($p->polzovatel_id ?? 0))
                    <div>
                        <dt class="text-slate-500">Риэлтор</dt>
                        <dd class="font-medium">{{ trim($p->realtor->familia.' '.$p->realtor->imya) }}</dd>
                        @if($p->realtor->telefon)
                            <dd class="text-slate-600 text-xs mt-0.5">{{ $p->realtor->telefon }}</dd>
                        @endif
                    </div>
                @endif
                @if($p->sozdal_kak)
                    <div>
                        <dt class="text-slate-500">Кто разместил</dt>
                        <dd class="font-medium">{{ \App\Support\PropertyListingAuthor::label($p->sozdal_kak) }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-slate-500">Собственников в реестре</dt>
                    <dd class="font-medium">{{ $ownersCount ?? 0 }}</dd>
                </div>
            </dl>
        </div>

        @include('properties.partials.property-owners-display')
    @endif

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
            @if($p->etazh)
                <div><dt class="text-slate-500">Этаж</dt><dd class="font-medium">{{ $p->etazh }}{{ $p->vsego_etazhey ? '/'.$p->vsego_etazhey : '' }}</dd></div>
            @endif
            @if($p->geo_shirota && $p->geo_dolgota)
                <div class="sm:col-span-2">
                    <dt class="text-slate-500">Координаты</dt>
                    <dd class="font-medium font-mono text-xs">{{ $p->geo_shirota }}, {{ $p->geo_dolgota }}</dd>
                </div>
            @endif
            <div><dt class="text-slate-500">Размещено</dt><dd class="font-medium">{{ $p->sozdano_at?->format('d.m.Y') ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Обновлено</dt><dd class="font-medium">{{ $p->obnovleno_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
        </dl>
        @if(!empty($houseRows))
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-sm font-semibold text-slate-800 mb-3">Параметры дома / участка</p>
                <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                    @foreach($houseRows as $row)
                        <div><dt class="text-slate-500">{{ $row['label'] }}</dt><dd class="font-medium">{{ $row['value'] }}</dd></div>
                    @endforeach
                </dl>
            </div>
        @endif
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
            @if(!($isRent ?? false))
                <a href="{{ route('pages.mortgage-calculator', ['price' => (int) $p->tsena, 'property_id' => $p->id]) }}" class="btn text-sm">Расчёт ипотеки</a>
            @endif
        </div>
    </div>

    <div class="card p-6">
        <h2 class="text-lg font-bold mb-1">Проверка документов</h2>
        <p class="text-sm text-slate-600 mb-4">{{ $requirementsSummary }}</p>
        <div class="h-2 rounded-full bg-slate-100 mb-4 overflow-hidden">
            <div class="h-full bg-brand-600 rounded-full" style="width: {{ $docsPercent }}%"></div>
        </div>
        <ul class="space-y-3">
            @foreach($documentRows ?? [] as $docRow)
                @php
                    $ok = ($docRow['state'] ?? '') === 'verified';
                    $rejected = ($docRow['state'] ?? '') === 'rejected';
                    $pending = ($docRow['state'] ?? '') === 'pending';
                @endphp
                <li class="rounded-lg border px-3 py-3 text-sm {{ $ok ? 'border-green-200 bg-green-50/50' : ($rejected ? 'border-red-200 bg-red-50/40' : 'border-slate-200') }}">
                    <div class="flex items-start justify-between gap-3">
                        <span class="font-medium">{{ $docRow['label'] }}</span>
                        <span class="font-medium shrink-0 {{ $ok ? 'text-green-700' : ($rejected ? 'text-red-700' : ($pending ? 'text-amber-700' : 'text-slate-500')) }}">
                            {{ $docRow['state_label'] }}
                        </span>
                    </div>
                    @if($isOwnerOrStaff ?? false)
                        <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-slate-500">
                            @if($docRow['has_file'] ?? false)
                                <span>Файл загружен</span>
                            @endif
                            @if(!empty($docRow['provereno_at']))
                                <span>Проверено {{ $docRow['provereno_at']->format('d.m.Y H:i') }}</span>
                            @endif
                        </div>
                        @if(!empty($docRow['kommentariy_mod']))
                            <p class="mt-2 text-xs text-red-800 bg-red-50 rounded px-2 py-1">{{ $docRow['kommentariy_mod'] }}</p>
                        @endif
                        @if(!empty($docRow['data_lines']))
                            <dl class="mt-2 grid sm:grid-cols-2 gap-x-4 gap-y-1 text-xs">
                                @foreach($docRow['data_lines'] as $line)
                                    <div>
                                        <dt class="text-slate-500 inline">{{ $line['label'] }}:</dt>
                                        <dd class="inline font-medium text-slate-800">{{ $line['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    @endif
                </li>
            @endforeach
        </ul>
        @if($isOwnerOrStaff ?? false)
            <a href="{{ route('properties.documents', $p) }}" class="btn-primary inline-block mt-4 text-sm">Управление документами</a>
        @endif
    </div>

    @if(($isOwnerOrStaff ?? false) && ($recentInquiries ?? collect())->isNotEmpty())
        <div class="card p-6">
            <h2 class="text-lg font-bold mb-4">Заявки на объект</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b">
                            <th class="pb-2 pr-3">Дата</th>
                            <th class="pb-2 pr-3">Имя</th>
                            <th class="pb-2 pr-3">Контакты</th>
                            <th class="pb-2">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentInquiries as $inq)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-3 text-slate-600">{{ $inq->sozdano_at?->format('d.m.Y H:i') }}</td>
                                <td class="py-2 pr-3 font-medium">{{ $inq->imya ?? '—' }}</td>
                                <td class="py-2 pr-3 text-slate-600">
                                    {{ $inq->telefon ?: '—' }}
                                    @if($inq->email)<br><span class="text-xs">{{ $inq->email }}</span>@endif
                                </td>
                                <td class="py-2">
                                    <span class="badge text-xs">{{ \App\Services\PropertyReportService::inquiryStatusLabel($inq) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($inquiriesCount > $recentInquiries->count())
                <p class="text-xs text-slate-500 mt-2">Показаны последние {{ $recentInquiries->count() }} из {{ $inquiriesCount }}.</p>
            @endif
        </div>
    @endif

    @if(($isOwnerOrStaff ?? false) && ($recentInfoRequests ?? collect())->isNotEmpty())
        <div class="card p-6">
            <h2 class="text-lg font-bold mb-4">Запросы дополнительной информации</h2>
            <ul class="space-y-3">
                @foreach($recentInfoRequests as $ir)
                    <li class="rounded-lg border border-slate-200 p-3 text-sm">
                        <div class="flex flex-wrap justify-between gap-2">
                            <span class="font-medium">{{ $ir->tipLabel() }}</span>
                            <span class="badge text-xs">{{ $ir->statusLabel() }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ $ir->sozdano_at?->format('d.m.Y H:i') }}
                            @if($ir->client) · {{ trim($ir->client->familia.' '.$ir->client->imya) }} @endif
                        </p>
                        @if($ir->messages->isNotEmpty())
                            <p class="text-xs text-slate-600 mt-2 line-clamp-2">{{ Str::limit($ir->messages->last()->tekst ?? '', 120) }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

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
                        @if(($isOwnerOrStaff ?? false) && $contract->relationLoaded('sellers') && $contract->sellers->isNotEmpty())
                            <p class="text-xs text-slate-600 mt-2">
                                Продавцы:
                                @foreach($contract->sellers as $seller)
                                    {{ $seller->user ? trim($seller->user->familia.' '.$seller->user->imya) : 'ID '.$seller->polzovatel_id }}
                                    ({{ number_format((float) $seller->dolya_procent, 0) }}%)@if(!$loop->last), @endif
                                @endforeach
                            </p>
                        @endif
                        @if($contract->buyer)
                            <p class="text-xs text-slate-600">Покупатель: {{ trim($contract->buyer->familia.' '.$contract->buyer->imya) }}</p>
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
                            @elseif($isOwnerOrStaff ?? false)
                                <p class="text-xs text-slate-500 mt-2">Детали договора доступны участникам сделки.</p>
                            @endif
                        @endauth
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if($canManage ?? false)
        @include('partials.status-version-history', ['versions' => $statusVersions ?? collect()])
    @endif

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
        @if($isOwnerOrStaff ?? false)
            <a href="{{ route('properties.edit', $p) }}" class="btn">Редактировать</a>
        @endif
        @if(($statusCode ?? '') === 'active' && Auth::check() && (int) Auth::id() !== (int) ($p->polzovatel_id ?? 0) && !($isRent ?? false))
            <a href="{{ route('purchase.buy', $p) }}" class="btn-primary">Купить онлайн</a>
        @endif
    </div>
</div>
@endsection
