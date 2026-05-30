@extends('layouts.app')

@section('title', 'Аналитика риэлтора')

@section('content')
@include('partials.realtor-nav')

<h1 class="text-3xl font-bold mb-2">Аналитика</h1>
<p class="text-sm text-slate-600 mb-6">Период с {{ $stats['period_from'] }}@if($allStaff) · сводка по агентству@endif</p>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="card p-4">
        <p class="text-xs text-slate-500 uppercase">Объявления</p>
        <p class="text-2xl font-bold">{{ $stats['properties_total'] }}</p>
        <p class="text-xs text-slate-600">активных: {{ $stats['properties_active'] }}, продано: {{ $stats['properties_sold'] }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-slate-500 uppercase">Клиенты CRM</p>
        <p class="text-2xl font-bold">{{ $stats['clients_total'] }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-slate-500 uppercase">Заявки (30 дн.)</p>
        <p class="text-2xl font-bold">{{ $stats['inquiries_total'] }}</p>
        <p class="text-xs text-slate-600">обработано: {{ $stats['inquiries_conversion'] }}%</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-slate-500 uppercase">Договоры</p>
        <p class="text-2xl font-bold">{{ $stats['contracts_period'] }}</p>
        <p class="text-xs text-slate-600">активных всего: {{ $stats['contracts_active'] }}</p>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-4">
    <div class="card p-6">
        <h2 class="font-bold mb-2">Заявки на подбор</h2>
        <p class="text-3xl font-bold text-brand-800">{{ $stats['selection_requests'] }}</p>
        <p class="text-sm text-slate-600 mt-1">за последние 30 дней</p>
    </div>
    <div class="card p-6">
        <h2 class="font-bold mb-3">Заявки по дням</h2>
        @if($stats['inquiries_by_day']->isEmpty())
            <p class="text-sm text-slate-500">Нет данных</p>
        @else
            <ul class="text-sm space-y-1 max-h-48 overflow-y-auto">
                @foreach($stats['inquiries_by_day'] as $day => $count)
                    <li class="flex justify-between"><span>{{ \Carbon\Carbon::parse($day)->format('d.m.Y') }}</span><span class="font-medium">{{ $count }}</span></li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
