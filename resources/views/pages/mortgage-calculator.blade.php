@extends('layouts.app')

@section('title', 'Ипотечный калькулятор')

@section('content')
@php
    $defaultPrice = (int) request('price', 5_000_000);
    $defaultDownPct = 20;
    $defaultDown = (int) round($defaultPrice * $defaultDownPct / 100);
@endphp

<div class="max-w-6xl mx-auto" data-mortgage-calculator id="mortgage-calculator-app">
    <div class="mb-8">
        <h1 class="text-3xl sm:text-4xl font-bold mb-2">Ипотечный калькулятор</h1>
        <p class="text-gray-600">Двигайте ползунки — расчёт обновляется сразу. Сравните банки и тип платежа.</p>
    </div>

    @if(!empty($property))
        <div id="mc-property-banner" class="mb-6 card p-4 border-brand-200 bg-brand-50/60 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-brand-800 font-medium">Объект из каталога</p>
                <p class="font-bold">{{ $property->nazvanie }}</p>
                <p class="text-sm text-gray-600">{{ $property->gorod }}, {{ $property->adres_ulitsy }}</p>
            </div>
            <a href="{{ route('properties.show', $property) }}" class="btn text-sm">К объявлению →</a>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 lg:gap-8">
        {{-- Параметры --}}
        <div class="xl:col-span-2 space-y-6">
            <div class="card p-6 sm:p-8">
                <h2 class="text-xl font-bold mb-6">Параметры кредита</h2>

                <div class="space-y-6">
                    <div>
                        <div class="flex justify-between items-baseline mb-2">
                            <label for="mc-price" class="form-label mb-0">Стоимость жилья</label>
                            <span class="text-sm font-semibold text-brand-800" id="mc-price-label">{{ number_format($defaultPrice, 0, ',', ' ') }} ₽</span>
                        </div>
                        <input type="range" id="mc-price-range" min="500000" max="50000000" step="100000" value="{{ $defaultPrice }}" class="mc-range w-full">
                        <input type="number" id="mc-price" min="1" step="1000" value="{{ $defaultPrice }}" class="form-input mt-2" data-no-search>
                    </div>

                    <div>
                        <div class="flex justify-between items-baseline mb-2">
                            <label for="mc-down-pct" class="form-label mb-0">Первоначальный взнос</label>
                            <span class="text-sm font-semibold text-slate-700"><span id="mc-down-pct-label">{{ $defaultDownPct }}</span>% · <span id="mc-down-amount-label">{{ number_format($defaultDown, 0, ',', ' ') }} ₽</span></span>
                        </div>
                        <input type="range" id="mc-down-pct-range" min="0" max="90" step="1" value="{{ $defaultDownPct }}" class="mc-range w-full">
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <input type="number" id="mc-down-pct" min="0" max="90" value="{{ $defaultDownPct }}" class="form-input text-sm" data-no-search>
                            <input type="number" id="mc-down-amount" min="0" step="1000" value="{{ $defaultDown }}" class="form-input text-sm" data-no-search>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">% от цены и сумма синхронизируются</p>
                    </div>

                    <div>
                        <div class="flex justify-between items-baseline mb-2">
                            <label for="mc-years" class="form-label mb-0">Срок кредита</label>
                            <span class="text-sm font-semibold text-slate-700"><span id="mc-years-label">20</span> лет</span>
                        </div>
                        <input type="range" id="mc-years-range" min="1" max="30" step="1" value="20" class="mc-range w-full">
                        <input type="number" id="mc-years" min="1" max="30" step="1" value="20" class="form-input mt-2 w-28" data-no-search>
                    </div>

                    <div>
                        <label class="form-label">Банк (ориентир по ставке)</label>
                        <div id="mc-bank-chips" class="flex flex-wrap gap-2 mb-3"></div>
                        <div class="flex justify-between items-baseline mb-2">
                            <label for="mc-rate" class="form-label mb-0 text-sm">Ставка</label>
                            <span class="text-sm font-semibold text-slate-700" id="mc-rate-label">8,2%</span>
                        </div>
                        <input type="range" id="mc-rate-range" min="3" max="25" step="0.1" value="8.2" class="mc-range w-full">
                        <input type="number" id="mc-rate" min="0.1" max="30" step="0.1" value="8.2" class="form-input mt-2 w-28" data-no-search>
                        <p class="text-xs text-gray-500 mt-2">Риэлтор направит заявку в банк — здесь ориентир полной стоимости кредита.</p>
                    </div>

                    <div>
                        <label for="mc-income" class="form-label">Семейный доход в месяц (необязательно)</label>
                        <input type="number" id="mc-income" min="0" step="1000" placeholder="Например, 120 000" class="form-input" data-no-search>
                        <p class="text-xs text-gray-500 mt-1">Покажем долю платежа в доходе (банки обычно смотрят до 30–40%).</p>
                    </div>

                    <div>
                        <span class="form-label block mb-2">Тип платежа</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <label class="mc-type-card has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/80">
                                <input type="radio" name="mc_payment_type" value="annuity" checked class="sr-only">
                                <span class="font-medium block">Аннуитет</span>
                                <span class="text-xs text-gray-600">Одинаковый платёж каждый месяц</span>
                            </label>
                            <label class="mc-type-card has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/80">
                                <input type="radio" name="mc_payment_type" value="differentiated" class="sr-only">
                                <span class="font-medium block">Дифференцированный</span>
                                <span class="text-xs text-gray-600">Меньше переплата, выше старт</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Результаты --}}
        <div class="xl:col-span-3 space-y-6">
            <div class="card p-6 sm:p-8 bg-gradient-to-br from-brand-900 to-brand-800 text-white border-0 shadow-lg">
                <p class="text-brand-200 text-sm mb-1">Ежемесячный платёж</p>
                <p class="text-4xl sm:text-5xl font-bold tracking-tight" id="mc-monthly-hero">—</p>
                <p class="text-brand-100 text-sm mt-2" id="mc-monthly-sub">Подстройте параметры слева</p>
                <div id="mc-income-load-badge" class="hidden mt-4 inline-block text-xs font-medium px-2.5 py-1 rounded-full border"></div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="card p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Сумма кредита</p>
                    <p class="font-bold text-sm sm:text-base" id="mc-loan-amount">—</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Переплата</p>
                    <p class="font-bold text-sm sm:text-base text-amber-700" id="mc-total-interest">—</p>
                    <p class="text-[10px] text-gray-500" id="mc-overpayment-pct"></p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Всего выплат</p>
                    <p class="font-bold text-sm sm:text-base" id="mc-total-payment">—</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Нагрузка</p>
                    <p class="font-bold text-sm sm:text-base" id="mc-income-load">—</p>
                </div>
            </div>

            <div class="card p-6 sm:p-8">
                <h3 class="font-bold mb-4">Структура выплат</h3>
                <div class="flex flex-col sm:flex-row items-center gap-8">
                    <div class="relative w-40 h-40 shrink-0">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0" stroke-width="12"/>
                            <circle id="mc-donut-principal" cx="50" cy="50" r="42" fill="none" stroke="#0d9488" stroke-width="12" stroke-linecap="round" stroke-dasharray="0 264"/>
                            <circle id="mc-donut-interest" cx="50" cy="50" r="42" fill="none" stroke="#f59e0b" stroke-width="12" stroke-linecap="round" stroke-dasharray="0 264"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center text-center pointer-events-none">
                            <span class="text-xs text-gray-500 leading-tight">тело +<br>проценты</span>
                        </div>
                    </div>
                    <div class="flex-1 space-y-3 w-full">
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-teal-600"></span>
                            <span class="text-sm text-gray-600 flex-1">Основной долг</span>
                            <span class="font-semibold" id="mc-legend-principal">—</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            <span class="text-sm text-gray-600 flex-1">Проценты банку</span>
                            <span class="font-semibold" id="mc-legend-interest">—</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-6 sm:p-8">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                    <h3 class="font-bold">График платежей</h3>
                    <button type="button" id="mc-schedule-toggle" class="btn text-sm">Показать весь график</button>
                </div>
                <p class="text-xs text-gray-500 mb-3" id="mc-schedule-count"></p>
                <div class="overflow-x-auto -mx-2 px-2">
                    <table class="w-full text-sm min-w-[32rem]">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wide">
                                <th class="text-left py-2 font-medium">Месяц</th>
                                <th class="text-right py-2 font-medium">Платёж</th>
                                <th class="text-right py-2 font-medium">Долг</th>
                                <th class="text-right py-2 font-medium">%</th>
                                <th class="text-right py-2 font-medium">Остаток</th>
                            </tr>
                        </thead>
                        <tbody id="mc-schedule-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-6 sm:p-8 mt-8 text-sm text-gray-600 leading-relaxed">
        <p class="mb-2"><strong class="text-slate-800">Справка.</strong> Аннуитет — удобнее планировать бюджет. Дифференцированный — меньше переплата, но первые месяцы платёж выше.</p>
        <p>Расчёт ориентировочный. Точные условия — в банке или у риэлтора агентства.</p>
    </div>
</div>

@endsection
