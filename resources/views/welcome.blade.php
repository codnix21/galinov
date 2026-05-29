{{-- Главная страница для всех посетителей. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Агентство недвижимости</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
        <header class="sticky top-0 z-50 border-b border-slate-200/90 bg-white/90 shadow-sm backdrop-blur-md safe-top">
            <nav class="container-custom py-3 sm:py-4 relative" id="siteNav">
                <div class="flex items-center justify-between gap-3">
                    <div class="shrink-0 pr-1">
                        @include('partials.site-brand', ['href' => url('/')])
                    </div>
                    @include('partials.mobile-nav', ['variant' => 'toggle'])
                    <div class="hidden lg:flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                        <a href="{{ route('properties.index') }}" class="nav-link whitespace-nowrap">
                            Объявления
                        </a>
                        <a href="{{ route('pages.mortgage-calculator') }}" class="nav-link whitespace-nowrap">
                            Ипотечный калькулятор
                        </a>
                        <a href="{{ route('pages.help') }}" class="nav-link whitespace-nowrap">
                            Помощь
                        </a>
                        @auth
                            @if(Auth::user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="nav-link whitespace-nowrap">
                                    Админ панель
                                </a>
                            @endif
                            <a href="{{ route('cabinet.index') }}" class="flex items-center gap-2 whitespace-nowrap rounded-xl border border-slate-200/90 bg-slate-50/80 px-3 py-1.5 transition-colors hover:border-brand-200 hover:bg-brand-50/60">
                                <span class="text-sm font-medium text-slate-800">{{ Auth::user()->name }}</span>
                                <span class="badge">
                                    @if(Auth::user()->role === 'admin') Админ
                                    @elseif(Auth::user()->role === 'realtor') Риэлтор
                                    @elseif(Auth::user()->role === 'client') Клиент
                                    @else Гость
                                    @endif
                                </span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="nav-link whitespace-nowrap border-red-200 text-red-800 hover:bg-red-50">
                                    Выход
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="nav-link whitespace-nowrap">
                                Вход
                            </a>
                            <a href="{{ route('register') }}" class="nav-link whitespace-nowrap">
                                Регистрация
                            </a>
                        @endauth
                    </div>
                </div>
                @include('partials.mobile-nav', ['variant' => 'drawer'])
            </nav>
        </header>

        <main class="container-custom py-16">
            <section class="mb-12 text-center">
                <h1 class="mb-6 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl md:text-6xl">
                    Агентство <span class="text-brand-700">недвижимости</span>
                </h1>
                <p class="mx-auto max-w-2xl text-balance text-lg text-slate-600 sm:text-xl">
                    Просмотр, покупка и оформление сделки онлайн — без обязательного визита к риэлтору
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('properties.index') }}" class="btn-primary">Смотреть объявления</a>
                    @auth
                        <a href="{{ route('cabinet.index') }}" class="btn">Личный кабинет</a>
                    @else
                        <a href="{{ route('login') }}" class="btn">Войти</a>
                    @endauth
                </div>
            </section>

            <section class="mb-20 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                <div class="card group p-8 transition-all hover:shadow-card-hover">
                    <h2 class="mb-4 text-2xl font-bold text-slate-900">Купить онлайн</h2>
                    <p class="mb-6 leading-relaxed text-slate-600">
                        Панорама, автодоговор, оплата и PDF — из дома, без риэлтора.
                    </p>
                    <a href="{{ route('properties.index', ['operation' => 'sale']) }}" class="btn">
                        Каталог →
                    </a>
                </div>
                <div class="card group p-8 transition-all hover:shadow-card-hover">
                    <h2 class="mb-4 text-2xl font-bold text-slate-900">Продать объект</h2>
                    <p class="mb-6 leading-relaxed text-slate-600">
                        Разместите объявление, загрузите документы на проверку, оформите экспресс-сделку — только данные покупателя.
                    </p>
                    @auth
                        <a href="{{ route('properties.create') }}" class="btn">
                            Подать объявление →
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="btn">
                            Регистрация →
                        </a>
                    @endauth
                </div>
                <div class="card group p-8 transition-all hover:shadow-card-hover">
                    <h2 class="mb-4 text-2xl font-bold text-slate-900">Стоимость кредита</h2>
                    <p class="mb-6 leading-relaxed text-slate-600">
                        Ипотека с выбором банка, полной переплатой и графиком платежей — риэлтор направляет заявку в банк.
                    </p>
                    <a href="{{ route('pages.mortgage-calculator') }}" class="btn">
                        Калькулятор →
                    </a>
                </div>
                <div class="card group p-8 transition-all hover:shadow-card-hover">
                    <h2 class="mb-4 text-2xl font-bold text-slate-900">Проверка документов</h2>
                    <p class="mb-6 leading-relaxed text-slate-600">
                        Паспорт, ЕГРН и право собственности проверяет модератор — как на Домклик.
                    </p>
                    @auth
                        <a href="{{ route('profile.documents.index') }}" class="btn">
                            Мои документы →
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn">
                            Войти →
                        </a>
                    @endauth
                </div>
            </section>

            <section class="divider pt-12">
                <h2 class="mb-8 text-center text-3xl font-bold text-slate-900">О системе</h2>
                <div class="mx-auto max-w-3xl space-y-6 leading-relaxed text-slate-700">
                    <p>
                        Информационная система агентства недвижимости предоставляет удобный интерфейс для работы с недвижимостью.
                    </p>
                    <p>
                        Агенство поддерживает вашу инициативу
                    </p>

                </div>
            </section>
        </main>

        @include('components.footer')
    </body>
</html>
