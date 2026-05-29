@php
    $user = Auth::user();
    $isStaff = $user->isRealtor() || $user->isAdmin();
@endphp

@if($isStaff)
    {{-- Компактное меню для риэлтора и админа --}}
    <a href="{{ route('properties.index') }}" class="nav-link nav-link--compact" title="Каталог">Каталог</a>
    <a href="{{ route('pages.mortgage-calculator') }}" class="nav-link nav-link--compact" title="Ипотечный калькулятор">Ипотека</a>

    <details class="nav-dropdown">
        <summary class="nav-link nav-link--compact nav-dropdown-trigger">CRM</summary>
        <div class="nav-dropdown-menu">
            <a href="{{ route('realtor.dashboard') }}" class="nav-dropdown-item">Рабочее место</a>
            @if($user->isStaff())
                <a href="{{ route('moderation.index') }}" class="nav-dropdown-item">Модерация</a>
                <a href="{{ route('moderation.documents') }}" class="nav-dropdown-item">Документы</a>
            @endif
            @if($user->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="nav-dropdown-item">Админ-панель</a>
            @endif
            <a href="{{ route('pages.training') }}" class="nav-dropdown-item">Обучение</a>
        </div>
    </details>

    <a href="{{ route('pages.help') }}" class="nav-link nav-link--compact">Помощь</a>
@else
    <a href="{{ route('properties.index') }}" class="nav-link whitespace-nowrap">Объявления</a>
    <a href="{{ route('pages.mortgage-calculator') }}" class="nav-link whitespace-nowrap">Ипотечный калькулятор</a>
    <a href="{{ route('pages.help') }}" class="nav-link whitespace-nowrap">Помощь</a>
@endif

<a href="{{ route('favorites.index') }}" class="nav-link nav-link--icon" title="Избранное">⭐</a>

<a href="{{ route('cabinet.index') }}" class="header-user-chip" title="{{ $user->name }}">
    <span class="header-user-chip__name">{{ $user->name }}</span>
    <span class="header-user-chip__role">
        @if($user->role === 'admin') Админ
        @elseif($user->role === 'realtor') Риэлтор
        @elseif($user->role === 'client') Клиент
        @else Гость
        @endif
    </span>
</a>

<form method="POST" action="{{ route('logout') }}" class="inline">
    @csrf
    <button type="submit" class="nav-link nav-link--compact nav-link--logout" title="Выйти">Выход</button>
</form>
