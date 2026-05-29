{{-- Главная страница админ-панели со статистикой. --}}
@extends('layouts.app')

@section('title', 'Панель управления администратора')

@section('content')
<div class="mb-8">
    <h1 class="text-4xl font-bold mb-2">Панель управления</h1>
    <p class="text-gray-600">Административная панель системы</p>
</div>

<!-- Статистика -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="card p-6">
        <div class="text-3xl font-bold mb-2">{{ $stats['total_users'] }}</div>
        <div class="text-sm text-gray-600">Пользователей</div>
    </div>
    <div class="card p-6">
        <div class="text-3xl font-bold mb-2">{{ $stats['total_properties'] }}</div>
        <div class="text-sm text-gray-600">Всего объявлений</div>
    </div>
    <div class="card p-6">
        <div class="text-3xl font-bold mb-2">{{ $stats['total_contracts'] }}</div>
        <div class="text-sm text-gray-600">Договоров</div>
    </div>
    <div class="card p-6">
        <div class="text-3xl font-bold mb-2">{{ $stats['active_contracts'] }}</div>
        <div class="text-sm text-gray-600">Активных договоров</div>
    </div>
</div>

@if(($stats['pending_moderation'] ?? 0) > 0)
<div class="mb-8 p-4 border-2 border-amber-400 bg-amber-50">
    <p class="font-medium">На модерации: {{ $stats['pending_moderation'] }} объявл.</p>
    <a href="{{ route('moderation.index') }}" class="text-sm underline mt-1 inline-block">Перейти к очереди →</a>
</div>
@endif

<!-- Быстрые ссылки -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <a href="{{ route('admin.users') }}" class="card p-8 group hover:shadow-lg transition-all">
        <h3 class="text-2xl font-bold mb-3 group-hover:underline">Управление пользователями</h3>
        <p class="text-gray-600">Просмотр, создание, редактирование и удаление пользователей</p>
    </a>
    <a href="{{ route('admin.properties') }}" class="card p-8 group hover:shadow-lg transition-all">
        <h3 class="text-2xl font-bold mb-3 group-hover:underline">Управление объявлениями</h3>
        <p class="text-gray-600">Просмотр, создание, редактирование и удаление объявлений</p>
    </a>
    <a href="{{ route('admin.contracts') }}" class="card p-8 group hover:shadow-lg transition-all">
        <h3 class="text-2xl font-bold mb-3 group-hover:underline">Управление договорами</h3>
        <p class="text-gray-600">Просмотр, создание, редактирование и удаление договоров</p>
    </a>
    <a href="{{ route('admin.reports') }}" class="card p-8 group hover:shadow-lg transition-all">
        <h3 class="text-2xl font-bold mb-3 group-hover:underline">Отчёты</h3>
        <p class="text-gray-600">Статистика и аналитика системы с фильтрами по датам</p>
    </a>
    <a href="{{ route('admin.audit-logs') }}" class="card p-8 group hover:shadow-lg transition-all">
        <h3 class="text-2xl font-bold mb-3 group-hover:underline">Журнал изменений</h3>
        <p class="text-gray-600">Логи действий с объявлениями, договорами и пользователями</p>
    </a>
    <a href="{{ route('admin.dictionaries') }}" class="card p-8 group hover:shadow-lg transition-all">
        <h3 class="text-2xl font-bold mb-3 group-hover:underline">Справочники</h3>
        <p class="text-gray-600">Города, статусы объявлений и договоров</p>
    </a>
    <a href="{{ route('admin.import') }}" class="card p-8 group hover:shadow-lg transition-all">
        <h3 class="text-2xl font-bold mb-3 group-hover:underline">Импорт данных</h3>
        <p class="text-gray-600">Загрузка объявлений из CSV / Excel</p>
    </a>
    <a href="{{ route('admin.database') }}" class="card p-8 group hover:shadow-lg transition-all">
        <h3 class="text-2xl font-bold mb-3 group-hover:underline">Резервная копия БД</h3>
        <p class="text-gray-600">Создание и восстановление базы данных</p>
    </a>
</div>

<!-- Последние объявления -->
<div class="card p-8 mb-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Последние объявления</h2>
        <a href="{{ route('admin.properties') }}" class="text-sm underline hover:no-underline">Все объявления →</a>
    </div>
    @if($recent_properties->count() > 0)
        <div class="space-y-4">
            @foreach($recent_properties as $property)
                <div class="flex items-center justify-between py-4 border-b border-gray-200 last:border-0">
                    <div>
                        <a href="{{ route('properties.show', $property) }}" class="font-medium hover:underline text-lg">
                            {{ $property->nazvanie }}
                        </a>
                        <p class="text-sm text-gray-600 mt-1">{{ trim($property->user->familia . ' ' . $property->user->imya . ' ' . $property->user->otchestvo) }} • {{ number_format($property->tsena, 0, ',', ' ') }} ₽</p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-600">Нет объявлений</p>
    @endif
</div>

<!-- Последние пользователи -->
<div class="card p-8 mb-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Последние пользователи</h2>
        <a href="{{ route('admin.users') }}" class="text-sm underline hover:no-underline">Все пользователи →</a>
    </div>
    @if($recent_users->count() > 0)
        <div class="space-y-4">
            @foreach($recent_users as $user)
                <div class="flex items-center justify-between py-4 border-b border-gray-200 last:border-0">
                    <div>
                        <p class="font-medium text-lg">{{ trim($user->familia . ' ' . $user->imya . ' ' . $user->otchestvo) }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ $user->email_polzovatela }} • {{ $user->properties_count }} объявлений</p>
                    </div>
                    <span class="badge">
                        @if($user->rol === 'admin') Админ
                        @elseif($user->rol === 'realtor') Риэлтор
                        @elseif($user->rol === 'client') Клиент
                        @else Гость
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-600">Нет пользователей</p>
    @endif
</div>

<!-- Последние договоры -->
<div class="card p-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Последние договоры</h2>
        <a href="{{ route('admin.contracts') }}" class="text-sm underline hover:no-underline">Все договоры →</a>
    </div>
    @if($recent_contracts->count() > 0)
        <div class="space-y-4">
            @foreach($recent_contracts as $contract)
                <div class="flex items-center justify-between py-4 border-b border-gray-200 last:border-0">
                    <div>
                        <p class="font-medium text-lg">Договор #{{ $contract->id }} - {{ $contract->property->nazvanie }}</p>
                        <p class="text-sm text-gray-600 mt-1">
                            @php
                                $buyer = $contract->buyer ?? $contract->client;
                                $owner = $contract->owner;
                            @endphp
                            @if($owner){{ trim($owner->familia . ' ' . $owner->imya) }} (влад.)@endif
                            @if($buyer) • {{ trim($buyer->familia . ' ' . $buyer->imya) }} (покуп.)@endif
                            • {{ number_format($contract->tsena, 0, ',', ' ') }} ₽
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-600">Нет договоров</p>
    @endif
</div>
@endsection
