{{-- Форма входа в аккаунт. --}}
<x-guest-layout>
    <div class="mb-8 text-center">
        <h2 class="text-3xl font-bold mb-2">Вход</h2>
        <p class="text-gray-600">Войдите в свой аккаунт</p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-slate-200 bg-slate-50/90 p-4 text-slate-800 shadow-sm">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-6">
            <label for="email" class="form-label">Email</label>
            <input id="email" 
                   class="form-input" 
                   type="email" 
                   name="email" 
                   value="{{ old('email') }}" 
                   required 
                   autofocus 
                   autocomplete="username" />
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="password" class="form-label">Пароль</label>
            <input id="password" 
                   class="form-input"
                   type="password"
                   name="password"
                   required 
                   autocomplete="current-password" />
            @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6 flex items-center">
            <input id="remember_me" 
                   type="checkbox" 
                   class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/40"
                   name="remember">
            <label for="remember_me" class="ml-2 text-sm">Запомнить меня</label>
        </div>

        <div class="flex items-center justify-between mb-6">
            @if (Route::has('password.request'))
                <a class="text-sm text-slate-600 underline decoration-slate-300 underline-offset-2 transition-colors hover:text-brand-700 hover:decoration-brand-400" href="{{ route('password.request') }}">
                    Забыли пароль?
                </a>
            @endif
            <button type="submit" class="btn-primary">
                Войти
            </button>
        </div>
    </form>

    <div class="divider pt-6 text-center">
        <a href="{{ route('register') }}" class="text-sm text-slate-600 underline decoration-slate-300 underline-offset-2 transition-colors hover:text-brand-700 hover:decoration-brand-400">
            Нет аккаунта? Зарегистрироваться
        </a>
    </div>
</x-guest-layout>
