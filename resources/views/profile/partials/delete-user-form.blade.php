{{-- Удаление своего аккаунта. --}}
<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">Удаление аккаунта</h2>
        <p class="mt-1 text-sm text-gray-600">
            После удаления вашего аккаунта все его ресурсы и данные будут безвозвратно удалены. Перед удалением аккаунта, пожалуйста, загрузите любые данные или информацию, которые вы хотите сохранить.
        </p>
    </header>

    <form method="post" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Вы уверены, что хотите удалить свой аккаунт? Это действие необратимо.')">
        @csrf
        @method('delete')

        <div class="mt-6">
            <label for="password" class="form-label">Введите пароль для подтверждения</label>
            <input id="password" name="password" type="password" class="form-input" placeholder="Пароль" required />
            @error('password', 'userDeletion')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-6 flex justify-end gap-4">
            <button type="submit" class="btn border-red-600 text-red-600 hover:bg-red-600 hover:text-white">
                Удалить аккаунт
            </button>
        </div>
    </form>
</section>
