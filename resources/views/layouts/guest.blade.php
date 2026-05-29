{{-- Макет для гостей: вход, регистрация — без бокового меню кабинета. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Агентство недвижимости')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-brand-50/40 text-slate-800 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center">
                    <a href="/" class="text-2xl font-bold tracking-tight text-slate-900 transition-colors hover:text-brand-700 sm:text-3xl">
                        Агентство недвижимости
                    </a>
                </div>
                <div class="card p-8">
                    @if(isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any())
                        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50/90 p-4 shadow-sm">
                            <p class="mb-2 font-medium text-red-800">Ошибки при заполнении формы:</p>
                            <ul class="list-inside list-disc space-y-1 text-sm text-red-700">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
