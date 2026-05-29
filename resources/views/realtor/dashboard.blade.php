@extends('layouts.app')

@section('title', 'Рабочее место риэлтора')

@section('content')
@include('partials.realtor-nav')

<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-4xl font-bold mb-2">Рабочее место риэлтора</h1>
        <p class="text-gray-600">
            @if($isAdmin)
                Сводка по агентству (режим администратора)
            @else
                Ваши объекты, сделки и очереди
            @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('properties.create') }}" class="btn-primary">+ Объявление</a>
        <a href="{{ route('contracts.create') }}" class="btn">+ Договор</a>
        <a href="{{ route('realtor.properties') }}" class="btn">Мои объекты</a>
    </div>
</div>

@include('partials.lean-actions')

<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
    <div class="card p-4">
        <div class="text-2xl font-bold">{{ $stats['my_properties'] }}</div>
        <div class="text-xs text-gray-600">{{ $isAdmin ? 'Объектов всего' : 'Моих объектов' }}</div>
    </div>
    <div class="card p-4">
        <div class="text-2xl font-bold text-green-700">{{ $stats['active_properties'] }}</div>
        <div class="text-xs text-gray-600">Активных</div>
    </div>
    <div class="card p-4 {{ $stats['pending_moderation'] > 0 ? 'border-amber-300 border-2' : '' }}">
        <div class="text-2xl font-bold">{{ $stats['pending_moderation'] }}</div>
        <div class="text-xs text-gray-600">На модерации</div>
    </div>
    <div class="card p-4">
        <div class="text-2xl font-bold">{{ $stats['drafts'] }}</div>
        <div class="text-xs text-gray-600">Черновиков</div>
    </div>
    <div class="card p-4 {{ $stats['moderation_queue'] > 0 ? 'border-amber-300 border-2' : '' }}">
        <div class="text-2xl font-bold">{{ $stats['moderation_queue'] }}</div>
        <div class="text-xs text-gray-600">Очередь модерации</div>
    </div>
    <div class="card p-4">
        <div class="text-2xl font-bold">{{ $stats['my_contracts'] }}</div>
        <div class="text-xs text-gray-600">{{ $isAdmin ? 'Договоров' : 'Моих договоров' }}</div>
    </div>
    <a href="{{ route('realtor.clients.index') }}" class="card p-4 hover:border-brand-200 transition-colors">
        <div class="text-2xl font-bold text-brand-800">{{ $stats['crm_clients'] }}</div>
        <div class="text-xs text-gray-600">Клиентов CRM</div>
    </a>
    <a href="{{ route('realtor.tasks.index') }}" class="card p-4 hover:border-brand-200 transition-colors">
        <div class="text-2xl font-bold">{{ $stats['crm_tasks_open'] }}</div>
        <div class="text-xs text-gray-600">Задач открыто</div>
    </a>
    <a href="{{ route('realtor.showings.index') }}" class="card p-4 hover:border-brand-200 transition-colors">
        <div class="text-2xl font-bold">{{ $stats['crm_showings_upcoming'] }}</div>
        <div class="text-xs text-gray-600">Показов впереди</div>
    </a>
    <a href="{{ route('realtor.collections.index') }}" class="card p-4 hover:border-brand-200 transition-colors">
        <div class="text-2xl font-bold">{{ $stats['crm_collections'] }}</div>
        <div class="text-xs text-gray-600">Подборок</div>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Договоры на подтверждении</h2>
            <a href="{{ route('contracts.pending') }}" class="text-sm text-brand-700 hover:underline">Все →</a>
        </div>
        @forelse($pendingContracts as $contract)
            <div class="py-3 border-b border-gray-100 last:border-0">
                <a href="{{ route('contracts.show', $contract) }}" class="font-medium hover:underline">
                    {{ $contract->property?->nazvanie ?? 'Договор #'.$contract->id }}
                </a>
                <div class="text-sm text-gray-600 mt-1">
                    {{ number_format($contract->tsena, 0, ',', ' ') }} ₽
                    @if($contract->needs_my_approval)
                        <span class="badge ml-1">Нужно ваше подтверждение</span>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-gray-500 text-sm">Нет договоров в ожидании</p>
        @endforelse
    </div>

    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Быстрые действия</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <a href="{{ route('moderation.index') }}" class="btn w-full text-left">
                Модерация
                @if($stats['moderation_queue'] > 0)
                    <span class="badge ml-1">{{ $stats['moderation_queue'] }}</span>
                @endif
            </a>
            <a href="{{ route('pages.training') }}" class="btn w-full text-left">Обучение</a>
            <a href="{{ route('properties.drafts') }}" class="btn w-full text-left">Черновики</a>
            <a href="{{ route('contracts.index') }}" class="btn w-full text-left">Все договоры</a>
            <a href="{{ route('realtor.clients.index') }}" class="btn w-full text-left">Клиенты CRM</a>
            <a href="{{ route('realtor.collections.create') }}" class="btn w-full text-left">Подборка</a>
        </div>
        <div class="mt-6 pt-4 border-t border-gray-100 text-sm text-gray-600">
            <p><strong>Продано:</strong> {{ $stats['sold'] }} · <strong>Сдано:</strong> {{ $stats['rented'] }}</p>
            <p class="mt-1"><strong>Активных сделок:</strong> {{ $stats['active_contracts'] }}</p>
        </div>
    </div>
</div>

@if($upcomingTasks->count() > 0 || $upcomingShowings->count() > 0)
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    @if($upcomingTasks->count() > 0)
    <div class="card p-6">
        <h2 class="font-bold mb-3">Ближайшие задачи</h2>
        @foreach($upcomingTasks as $t)
            <div class="text-sm py-2 border-b">{{ $t->nazvanie }} @if($t->srok_do)<span class="text-gray-500">· {{ $t->srok_do->format('d.m H:i') }}</span>@endif</div>
        @endforeach
        <a href="{{ route('realtor.tasks.index') }}" class="text-sm text-brand-700 mt-2 inline-block">Все задачи →</a>
    </div>
    @endif
    @if($upcomingShowings->count() > 0)
    <div class="card p-6">
        <h2 class="font-bold mb-3">Ближайшие показы</h2>
        @foreach($upcomingShowings as $s)
            <div class="text-sm py-2 border-b">{{ $s->property?->nazvanie }} · {{ $s->naznacheno_na->format('d.m H:i') }}</div>
        @endforeach
        <a href="{{ route('realtor.showings.index') }}" class="text-sm text-brand-700 mt-2 inline-block">Все показы →</a>
    </div>
    @endif
</div>
@endif

<div class="card p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold">Недавние объекты</h2>
        <a href="{{ route('realtor.properties') }}" class="text-sm text-brand-700 hover:underline">Управление →</a>
    </div>
    @if($recentProperties->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b">
                        <th class="pb-2 pr-4">Название</th>
                        <th class="pb-2 pr-4">Статус</th>
                        <th class="pb-2 pr-4">Цена</th>
                        <th class="pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentProperties as $property)
                        <tr class="border-b border-gray-50">
                            <td class="py-3 pr-4 font-medium">{{ Str::limit($property->nazvanie, 40) }}</td>
                            <td class="py-3 pr-4"><span class="badge">{{ $property->status_name }}</span></td>
                            <td class="py-3 pr-4">{{ number_format($property->tsena, 0, ',', ' ') }} ₽</td>
                            <td class="py-3 text-right">
                                <a href="{{ route('properties.show', $property) }}" class="text-brand-700 hover:underline">Открыть</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-gray-500">Пока нет объектов. <a href="{{ route('properties.create') }}" class="text-brand-700 hover:underline">Создать первое объявление</a></p>
    @endif
</div>

@include('partials.realtor-training')
@endsection
