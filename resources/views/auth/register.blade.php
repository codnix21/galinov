{{-- Регистрация нового пользователя (роль «клиент» по умолчанию). --}}
<x-guest-layout>
    <div class="mb-8 text-center">
        <h2 class="text-3xl font-bold mb-2">Регистрация</h2>
        <p class="text-gray-600">Создайте новый аккаунт</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-6">
            <label for="familia" class="form-label">Фамилия</label>
            <input id="familia"
                   class="form-input @error('familia') border-red-600 bg-red-50 @enderror"
                   type="text"
                   name="familia"
                   value="{{ old('familia') }}"
                   required
                   autofocus
                   autocomplete="family-name" />
            @error('familia')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="imya" class="form-label">Имя</label>
            <input id="imya"
                   class="form-input @error('imya') border-red-600 bg-red-50 @enderror"
                   type="text"
                   name="imya"
                   value="{{ old('imya') }}"
                   required
                   autocomplete="given-name" />
            @error('imya')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="otchestvo" class="form-label">Отчество</label>
            <input id="otchestvo"
                   class="form-input @error('otchestvo') border-red-600 bg-red-50 @enderror"
                   type="text"
                   name="otchestvo"
                   value="{{ old('otchestvo') }}"
                   autocomplete="additional-name" />
            @error('otchestvo')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="email_polzovatela" class="form-label">Email</label>
            <input id="email_polzovatela"
                   class="form-input @error('email_polzovatela') border-red-600 bg-red-50 @enderror"
                   type="email"
                   name="email_polzovatela"
                   value="{{ old('email_polzovatela') }}"
                   required
                   autocomplete="username" />
            @error('email_polzovatela')
                <p class="mt-2 text-sm text-red-600 font-medium">
                    {{ $message }}
                    @if(str_contains($message, 'уже зарегистрирован'))
                        <a href="{{ route('login') }}" class="underline hover:no-underline ml-1">Войти?</a>
                    @endif
                </p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="telefon" class="form-label">Телефон</label>
            <input id="telefon"
                   class="form-input @error('telefon') border-red-600 bg-red-50 @enderror"
                   type="tel"
                   name="telefon"
                   value="{{ old('telefon') }}"
                   placeholder="+7 (999) 123-45-67"
                   autocomplete="tel" />
            @error('telefon')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="parol" class="form-label">Пароль</label>
            <input id="parol"
                   class="form-input @error('parol') border-red-600 bg-red-50 @enderror"
                   type="password"
                   name="parol"
                   required
                   autocomplete="new-password" />
            @error('parol')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="parol_confirmation" class="form-label">Подтвердите пароль</label>
            <input id="parol_confirmation"
                   class="form-input"
                   type="password"
                   name="parol_confirmation"
                   required
                   autocomplete="new-password" />
        </div>

        <div class="flex items-center justify-between mb-6">
            <a class="text-sm underline hover:no-underline" href="{{ route('login') }}">
                Уже зарегистрированы?
            </a>
            <button type="submit" class="btn-primary">
                Зарегистрироваться
            </button>
        </div>
    </form>
</x-guest-layout>
