{{-- Блок смены ФИО, телефона, аватара. --}}
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Информация профиля
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Обновите информацию о вашем профиле и адрес электронной почты.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data" data-validate novalidate>
        @csrf
        @method('patch')

        @if($errors->any())
            <div class="mb-4 p-4 border-2 border-red-500 bg-red-50">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li class="text-sm text-red-600">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label for="familia" class="form-label">Фамилия</label>
            <input id="familia" name="familia" type="text" class="form-input" value="{{ old('familia', $user->familia ?? explode(' ', $user->name)[0] ?? '') }}" required autofocus autocomplete="family-name" />
            @error('familia')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="imya" class="form-label">Имя</label>
            <input id="imya" name="imya" type="text" class="form-input" value="{{ old('imya', $user->imya ?? explode(' ', $user->name)[1] ?? '') }}" required autocomplete="given-name" />
            @error('imya')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="otchestvo" class="form-label">Отчество</label>
            <input id="otchestvo" name="otchestvo" type="text" class="form-input" value="{{ old('otchestvo', $user->otchestvo ?? explode(' ', $user->name)[2] ?? '') }}" autocomplete="additional-name" />
            @error('otchestvo')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="telefon" class="form-label">Телефон</label>
            <input id="telefon" name="telefon" type="tel" class="form-input @error('telefon') border-red-500 @enderror"
                   value="{{ old('telefon', $user->telefon) }}" autocomplete="tel"
                   placeholder="+7 (999) 123-45-67" />
            @error('telefon')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="pol" class="form-label">Пол</label>
            <select id="pol" name="pol" class="form-input @error('pol') border-red-500 @enderror">
                <option value="" @selected(old('pol', $user->pol ?? '') === '')>Не указан</option>
                <option value="male" @selected(old('pol', $user->pol ?? '') === 'male')>Мужской</option>
                <option value="female" @selected(old('pol', $user->pol ?? '') === 'female')>Женский</option>
            </select>
            @error('pol')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email_polzovatela" class="form-label">Email</label>
            <input id="email_polzovatela" name="email_polzovatela" type="email" class="form-input" value="{{ old('email_polzovatela', $user->email_polzovatela ?? $user->email) }}" required autocomplete="username" />
            @error('email_polzovatela')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        Ваш адрес электронной почты не подтвержден.

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            Нажмите здесь, чтобы отправить письмо с подтверждением повторно.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            Новая ссылка для подтверждения была отправлена на ваш адрес электронной почты.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <label for="biografiya" class="form-label">О себе</label>
            <textarea id="biografiya" name="biografiya" rows="4" class="form-input">{{ old('biografiya', $user->biografiya ?? $user->bio) }}</textarea>
            @error('biografiya')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Расскажите о себе (максимум 1000 символов)</p>
        </div>

        <div>
            <label for="avatar_polzovatela" class="form-label">Фотография профиля</label>
            @if($user->avatar_url)
                <div class="mt-2 mb-2">
                    <img src="{{ $user->avatar_url }}" alt="Аватар" class="w-24 h-24 rounded-full object-cover">
                </div>
            @endif
            <input id="avatar_polzovatela" name="avatar_polzovatela" type="file" accept="image/*" class="form-input">
            @error('avatar_polzovatela')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Загрузите фотографию профиля (максимум 2MB)</p>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">Сохранить</button>

            @if (session('status') === 'profile-updated')
                <p class="text-sm text-green-600">
                    Сохранено.
                </p>
            @endif
        </div>
    </form>
</section>
