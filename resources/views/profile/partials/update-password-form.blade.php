{{-- Блок смены пароля. --}}
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Смена пароля</h2>
        <p class="mt-1 text-sm text-gray-600">
            Убедитесь, что ваш аккаунт использует длинный случайный пароль для безопасности.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="form-label">Текущий пароль</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-input" autocomplete="current-password" />
            @error('current_password', 'updatePassword')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="update_password_password" class="form-label">Новый пароль</label>
            <input id="update_password_password" name="password" type="password" class="form-input" autocomplete="new-password" />
            @error('password', 'updatePassword')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="update_password_password_confirmation" class="form-label">Подтвердите пароль</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-input" autocomplete="new-password" />
            @error('password_confirmation', 'updatePassword')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">Сохранить</button>

            @if (session('status') === 'password-updated')
                <p class="text-sm text-green-600">
                    Сохранено.
                </p>
            @endif
        </div>
    </form>
</section>
