{{-- Основной макет сайта: шапка, меню, подвал, подключение стилей и скриптов. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Агентство недвижимости')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
        {{-- Шапка: логотип, каталог, меню по роли (клиент / риелтор / админ) --}}
        <header class="sticky top-0 z-50 border-b border-slate-200/90 bg-white shadow-sm safe-top lg:bg-white/90 lg:backdrop-blur-md">
            <nav class="container-custom relative overflow-visible py-2.5 sm:py-3" id="siteNav">
                <div class="flex items-center justify-between gap-3">
                    <div class="shrink-0 pr-1">
                        @include('partials.site-brand', ['href' => url('/')])
                    </div>
                    <div class="flex min-w-0 flex-1 items-center justify-end gap-1 sm:gap-1.5">
                    @auth
                        @include('partials.notifications-bell')
                    @endauth
                    @include('partials.mobile-nav', ['variant' => 'toggle'])
                    <div class="hidden lg:flex flex-nowrap items-center justify-end gap-1.5 xl:gap-2 min-w-0">
                        @auth
                            @include('partials.header-auth-nav')
                        @else
                            <a href="{{ route('properties.index') }}" class="nav-link whitespace-nowrap">Объявления</a>
                            <a href="{{ route('pages.mortgage-calculator') }}" class="nav-link whitespace-nowrap">Ипотека</a>
                            <a href="{{ route('pages.help') }}" class="nav-link whitespace-nowrap">Помощь</a>
                            <a href="{{ route('login') }}" class="nav-link whitespace-nowrap">Вход</a>
                            <a href="{{ route('register') }}" class="nav-link whitespace-nowrap">Регистрация</a>
                        @endauth
                    </div>
                    </div>
                </div>
                @include('partials.mobile-nav', ['variant' => 'drawer'])
            </nav>
        </header>

        {{-- Контент страницы + flash-сообщения об успехе и ошибках --}}
        <main class="container-custom py-5 sm:py-8 min-h-[calc(100vh-200px)] pb-safe">
            @if(isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50/90 p-4 shadow-sm" role="alert">
                    <p class="mb-2 font-semibold text-red-900">Исправьте ошибки в форме:</p>
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="text-sm text-red-800">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        @include('components.footer')
        <x-toast-stack />
        <x-confirm-modal />
        @stack('scripts')
    </body>
</html>
