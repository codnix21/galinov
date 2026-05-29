@if(($variant ?? '') === 'toggle')
    <div class="flex shrink-0 items-center lg:hidden">
        <button
            type="button"
            class="mobile-nav-toggle"
            aria-label="Меню"
            aria-expanded="false"
            aria-controls="mobileNav"
            id="mobileNavBtn"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </div>
@endif

@if(($variant ?? '') === 'drawer')
    <div id="mobileNavBackdrop" class="mobile-nav-backdrop hidden lg:hidden" aria-hidden="true"></div>
    <div id="mobileNav" class="mobile-nav-panel hidden lg:hidden" role="dialog" aria-modal="true" aria-label="Меню сайта">
        <div class="py-3 flex flex-col gap-0.5">
            <a href="{{ route('properties.index') }}" class="mobile-nav-link">Объявления</a>
            <a href="{{ route('properties.map') }}" class="mobile-nav-link">Карта</a>
            <a href="{{ route('pages.mortgage-calculator') }}" class="mobile-nav-link">Ипотека</a>
            <a href="{{ route('pages.help') }}" class="mobile-nav-link">Помощь</a>
            @auth
                <div class="divider my-2"></div>
                <p class="px-3 py-1 text-xs font-medium text-slate-500">{{ Auth::user()->name }}</p>
                <a href="{{ route('cabinet.index') }}" class="mobile-nav-link font-semibold text-slate-900">Личный кабинет</a>
                <a href="{{ route('profile.edit') }}" class="mobile-nav-link">Профиль</a>
                <a href="{{ route('favorites.index') }}" class="mobile-nav-link">Избранное</a>
                <a href="{{ route('notifications.index') }}" class="mobile-nav-link">
                    Уведомления
                    @if(($unreadNotificationsCount ?? 0) > 0)
                        <span class="ml-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white">
                            {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('contracts.index') }}" class="mobile-nav-link">Договоры</a>
                @if(Auth::user()->isRealtor() || Auth::user()->isAdmin())
                    <a href="{{ route('realtor.dashboard') }}" class="mobile-nav-link mobile-nav-link--accent">Рабочее место</a>
                    <a href="{{ route('realtor.inquiries.index') }}" class="mobile-nav-link">Заявки клиентов</a>
                @endif
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="mobile-nav-link">Админ панель</a>
                @endif
                @if(Auth::user()->isStaff())
                    <a href="{{ route('moderation.index') }}" class="mobile-nav-link">Модерация</a>
                    <a href="{{ route('moderation.documents') }}" class="mobile-nav-link">Документы</a>
                @endif
                @if(Auth::user()->isRealtor() || Auth::user()->isAdmin())
                    <a href="{{ route('pages.training') }}" class="mobile-nav-link">Обучение</a>
                @endif
                <div class="divider my-3"></div>
                <form method="POST" action="{{ route('logout') }}" class="px-3 pb-2">
                    @csrf
                    <button type="submit" class="mobile-nav-logout">
                        Выйти из аккаунта
                    </button>
                </form>
            @else
                <div class="divider my-2"></div>
                <a href="{{ route('login') }}" class="mobile-nav-link">Вход</a>
                <a href="{{ route('register') }}" class="mobile-nav-link">Регистрация</a>
            @endauth
        </div>
    </div>
@endif
