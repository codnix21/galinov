<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Персональные данные
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Эти данные нужны для подготовки договора и проверки личности. Хранятся в системе в зашифрованном виде.
        </p>
    </header>

    @php
        $pd = $user->personalData;
    @endphp

    <form method="post" action="{{ route('profile.personal.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="pasport_seriya_nomer" class="form-label">Серия и номер паспорта</label>
                <input
                    id="pasport_seriya_nomer"
                    name="pasport_seriya_nomer"
                    type="text"
                    class="form-input"
                    maxlength="11"
                    inputmode="numeric"
                    autocomplete="off"
                    pattern="\d{4}\s?\d{6}"
                    title="4 цифры серии и 6 цифр номера, например 1234 567890"
                    value="{{ old('pasport_seriya_nomer', $pd->pasport_seriya_nomer ?? '') }}"
                    placeholder="1234 567890"
                >
                <p class="mt-1 text-xs text-slate-500">10 цифр: серия (4) + номер (6)</p>
                @error('pasport_seriya_nomer')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="pasport_data_vydachi" class="form-label">Дата выдачи</label>
                <input
                    id="pasport_data_vydachi"
                    name="pasport_data_vydachi"
                    type="date"
                    class="form-input"
                    min="1990-01-01"
                    max="{{ now()->format('Y-m-d') }}"
                    value="{{ old('pasport_data_vydachi', optional($pd->pasport_data_vydachi ?? null)?->format('Y-m-d')) }}"
                >
                @error('pasport_data_vydachi')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="pasport_kem_vydan" class="form-label">Кем выдан</label>
            <input
                id="pasport_kem_vydan"
                name="pasport_kem_vydan"
                type="text"
                class="form-input"
                maxlength="255"
                value="{{ old('pasport_kem_vydan', $pd->pasport_kem_vydan ?? '') }}"
                placeholder="Например: УМВД России по ..."
            >
            @error('pasport_kem_vydan')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="inn" class="form-label">ИНН</label>
                <input
                    id="inn"
                    name="inn"
                    type="text"
                    class="form-input"
                    maxlength="12"
                    inputmode="numeric"
                    pattern="\d{10,12}"
                    title="10 цифр для физлица или 12 для ИП/организации"
                    value="{{ old('inn', $pd->inn ?? '') }}"
                    placeholder="10–12 цифр"
                >
                @error('inn')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="snils" class="form-label">СНИЛС</label>
                <input
                    id="snils"
                    name="snils"
                    type="text"
                    class="form-input"
                    maxlength="14"
                    inputmode="numeric"
                    pattern="\d{3}-?\d{3}-?\d{3}\s?\d{2}"
                    title="Формат XXX-XXX-XXX XX"
                    value="{{ old('snils', $pd->snils ?? '') }}"
                    placeholder="123-456-789 01"
                >
                @error('snils')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">Сохранить</button>
            @if (session('status') === 'personal-updated')
                <p class="text-sm text-green-600">Сохранено.</p>
            @endif
        </div>
    </form>
</section>
