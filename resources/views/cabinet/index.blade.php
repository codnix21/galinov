{{-- Личный кабинет: сводка по роли. --}}
@extends('layouts.app')

@section('title', 'Личный кабинет')

@section('content')
<div class="mb-8 flex flex-wrap justify-between gap-4 items-start">
    <div>
        <h1 class="text-3xl sm:text-4xl font-bold mb-2">Личный кабинет</h1>
        <p class="text-gray-600">Добро пожаловать, {{ trim($user->familia . ' ' . $user->imya . ' ' . $user->otchestvo) }}!</p>
    </div>
    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
        @csrf
        <button type="submit" class="btn border-red-200 text-red-800 hover:bg-red-50 w-full sm:w-auto">Выйти из аккаунта</button>
    </form>
</div>

@include('partials.lean-actions')

<!-- Статистика -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="card p-6">
        <div class="text-3xl font-bold mb-2">{{ $stats['total_properties'] }}</div>
        <div class="text-sm text-gray-600">Всего объявлений</div>
    </div>
    <div class="card p-6">
        <div class="text-3xl font-bold mb-2">{{ $stats['active_properties'] }}</div>
        <div class="text-sm text-gray-600">Активных</div>
    </div>
    @if(($stats['pending_moderation_properties'] ?? 0) > 0)
    <div class="card p-6 border-2 border-amber-300">
        <div class="text-3xl font-bold mb-2">{{ $stats['pending_moderation_properties'] }}</div>
        <div class="text-sm text-gray-600">На модерации</div>
    </div>
    @endif
    <div class="card p-6">
        <div class="text-3xl font-bold mb-2">{{ $stats['sold_properties'] }}</div>
        <div class="text-sm text-gray-600">Продано</div>
    </div>
    @if(isset($stats['pending_contracts']))
        <div class="card p-6 {{ ($stats['pending_contracts'] ?? 0) > 0 ? 'border-2 border-amber-300' : '' }}">
            <div class="text-3xl font-bold mb-2">{{ $stats['pending_contracts'] }}</div>
            <div class="text-sm text-gray-600">На подтверждение (риэлтор)</div>
        </div>
    @endif
    @if(isset($stats['total_contracts']))
        <div class="card p-6">
            <div class="text-3xl font-bold mb-2">{{ $stats['total_contracts'] }}</div>
            <div class="text-sm text-gray-600">Всего договоров</div>
        </div>
    @endif
</div>

@if($user->isRealtor())
    @include('partials.realtor-training')
@endif

<!-- Информация о пользователе -->
<div class="card p-8 mb-8">
    <h2 class="text-2xl font-bold mb-6">Информация о профиле</h2>
    <div class="grid grid-cols-2 gap-6">
        <div class="pb-4 border-b border-gray-200">
            <span class="text-sm text-gray-600 block mb-1">Имя</span>
            <span class="font-medium text-lg">{{ trim($user->familia . ' ' . $user->imya . ' ' . $user->otchestvo) }}</span>
        </div>
        <div class="pb-4 border-b border-gray-200">
            <span class="text-sm text-gray-600 block mb-1">Email</span>
            <span class="font-medium text-lg">{{ $user->email_polzovatela }}</span>
        </div>
        <div class="pb-4 border-b border-gray-200">
            <span class="text-sm text-gray-600 block mb-1">Роль</span>
            <span class="font-medium text-lg">
                @if($user->rol === 'admin') Администратор
                @elseif($user->rol === 'realtor') Риэлтор
                @elseif($user->rol === 'client') Клиент
                @else Гость
                @endif
            </span>
        </div>
        <div class="pb-4 border-b border-gray-200">
            <span class="text-sm text-gray-600 block mb-1">Дата регистрации</span>
            <span class="font-medium text-lg">{{ $user->sozdano_at ? $user->sozdano_at->format('d.m.Y') : ($user->created_at ? $user->created_at->format('d.m.Y') : 'Не указана') }}</span>
        </div>
    </div>
    <div class="mt-6">
        <a href="{{ route('profile.edit') }}" class="btn">
            Редактировать профиль
        </a>
    </div>
</div>

<!-- Мои объявления -->
<div class="mb-6 flex items-center justify-between">
    <h2 class="text-2xl font-bold">Мои объявления</h2>
    <div class="flex gap-3">
        <a href="{{ route('properties.drafts') }}" class="btn">
            Черновики
        </a>
        <a href="{{ route('properties.create') }}" class="btn-primary">
            + Создать объявление
        </a>
    </div>
</div>

@if($properties->count() > 0)
    <div class="space-y-4">
        @foreach($properties as $property)
            <div class="card p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="mb-3 flex items-center gap-2 flex-wrap">
                            <span class="badge">{{ $property->type_name }}</span>
                            <span class="badge">{{ $property->operation_name }}</span>
                            <span class="badge">{{ $property->status_name }}</span>
                        </div>
                        <h3 class="text-xl font-bold mb-2">
                            <a href="{{ route('properties.show', $property) }}" class="hover:underline">
                                {{ $property->nazvanie }}
                            </a>
                        </h3>
                        <p class="text-gray-600 mb-3">{{ ($property->gorod ?? '') . ', ' . ($property->adres_ulitsy ?? '') }}</p>
                        <div class="flex items-center gap-6 text-sm text-gray-600">
                            @if($property->ploshchad)
                                <span>Площадь: <span class="font-medium">{{ $property->ploshchad }} м²</span></span>
                            @endif
                            @if($property->komnaty)
                                <span>Комнат: <span class="font-medium">{{ $property->komnaty }}</span></span>
                            @endif
                            <span>Цена: <span class="font-medium">{{ number_format($property->tsena, 0, ',', ' ') }} ₽</span></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 ml-6">
                        <a href="{{ route('properties.show', $property) }}" class="btn">
                            Просмотр
                        </a>
                        <a href="{{ route('properties.edit', $property) }}" class="btn">
                            Редактировать
                        </a>
                        <form action="{{ route('properties.destroy', $property) }}" method="POST" onsubmit="return confirm('Удалить это объявление?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn hover:bg-red-600 hover:border-red-600 hover:text-white">
                                Удалить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8">
        {{ $properties->links() }}
    </div>
@else
    <div class="card p-12 text-center">
        <p class="text-xl text-gray-600 mb-4">У вас пока нет объявлений</p>
        <a href="{{ route('properties.create') }}" class="btn-primary inline-block">
            Создать первое объявление
        </a>
    </div>
@endif

@if($user->isClient())
    <!-- Мои договоры -->
    <div class="mb-6 flex items-center justify-between mt-12">
        <h2 class="text-2xl font-bold">Мои сделки</h2>
        <a href="{{ route('properties.index', ['operation' => 'sale']) }}" class="btn-primary">
            Каталог — купить онлайн
        </a>
    </div>
    <p class="text-sm text-slate-600 mb-4">
        Договор создаётся автоматически при онлайн-покупке или экспресс-сделке на карточке объявления.
        Здесь — только ваши сделки, где вы участник (покупатель или продавец).
    </p>
    <div class="mb-6">
        <a href="{{ route('contracts.index') }}" class="btn">
            Мои договоры
        </a>
        @if(isset($stats['pending_contracts']) && $stats['pending_contracts'] > 0)
            <span class="ml-2 text-sm text-yellow-600">
                ({{ $stats['pending_contracts'] }} ожидают подтверждения)
            </span>
        @endif
    </div>
@endif

@if($user->isRealtor() || $user->isAdmin())
    <!-- Договоры на подтверждение -->
    <div class="mb-6 flex items-center justify-between mt-12">
        <h2 class="text-2xl font-bold">Договоры</h2>
        <div class="flex gap-3">
            <a href="{{ route('contracts.index') }}" class="btn">Все договоры</a>
            <a href="{{ route('contracts.create') }}" class="btn-primary">
                + Создать договор
            </a>
        </div>
    </div>
    <div class="mb-6">
        <a href="{{ route('contracts.pending') }}" class="btn-primary">
            Модерация: договоры на подтверждение
        </a>
        @if(isset($stats['pending_contracts']) && $stats['pending_contracts'] > 0)
            <span class="ml-2 text-sm text-yellow-600">
                ({{ $stats['pending_contracts'] }} ожидают подтверждения)
            </span>
        @endif
    </div>
@endif
@endsection
