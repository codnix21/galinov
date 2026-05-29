{{-- Создание договора в админке. --}}
@extends('layouts.app')

@section('title', 'Создать договор')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Создать договор</h1>
        <p class="text-gray-600">Продажа или аренда — тип договора должен совпадать с объявлением.</p>
        <p class="text-sm text-gray-500 mt-2">Для аренды пользователи распечатывают бланк со страницы договора и загружают скан подписанного экземпляра.</p>
    </div>

    @if(count($propertyItems) === 0)
        <div class="mb-6 p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-sm">
            Нет активных объектов. Сначала опубликуйте объявление в каталоге.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.contracts.store') }}" class="card p-8" id="admin_contract_form">
        @csrf

        @include('partials.fio-search-select', [
            'id' => 'nedvizhimost_id',
            'name' => 'nedvizhimost_id',
            'label' => 'Объект недвижимости',
            'required' => true,
            'placeholder' => 'Поиск по названию, городу или адресу…',
            'searchUrl' => route('api.contracts.search.properties'),
            'selected' => old('nedvizhimost_id'),
            'items' => $propertyItems,
        ])

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            @include('partials.fio-search-select', [
                'id' => 'vladelets_id',
                'name' => 'vladelets_id',
                'label' => 'Сторона 1: владелец объекта',
                'required' => true,
                'placeholder' => 'Поиск по ФИО или email…',
                'searchUrl' => route('api.contracts.search.clients'),
                'selected' => old('vladelets_id'),
                'items' => $clientItems,
            ])

            @include('partials.fio-search-select', [
                'id' => 'pokupatel_id',
                'name' => 'pokupatel_id',
                'label' => 'Сторона 2: покупатель',
                'required' => true,
                'placeholder' => 'Поиск по ФИО или email…',
                'searchUrl' => route('api.contracts.search.clients'),
                'selected' => old('pokupatel_id'),
                'items' => $clientItems,
            ])
        </div>

        @include('partials.fio-search-select', [
            'id' => 'rieltor_id',
            'name' => 'rieltor_id',
            'label' => 'Риэлтор сделки',
            'required' => true,
            'placeholder' => 'Поиск по ФИО или email…',
            'searchUrl' => route('api.contracts.search.realtors'),
            'selected' => old('rieltor_id'),
            'items' => $realtorItems,
        ])

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="tip" class="form-label">Тип договора *</label>
                <select id="tip" name="tip" required class="form-input">
                    <option value="sale" {{ old('tip', 'sale') == 'sale' ? 'selected' : '' }}>Купля-продажа</option>
                    <option value="rent" {{ old('tip') == 'rent' ? 'selected' : '' }}>Аренда</option>
                </select>
                @error('tip')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="tsena" class="form-label"><span id="price_label_admin">Цена (₽) *</span></label>
                <input type="number" id="tsena" name="tsena" value="{{ old('tsena') }}" step="0.01" min="0" required class="form-input">
                <p class="mt-1 text-xs text-gray-500" id="price_hint_admin">Для аренды укажите месячную плату.</p>
                @error('tsena')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mb-6">
            <label for="data_nachala" class="form-label">Дата начала *</label>
            <input type="date" id="data_nachala" name="data_nachala" value="{{ old('data_nachala') }}" required class="form-input">
            @error('data_nachala')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6" id="rent_end_block_admin" style="display: none;">
            <label for="data_okonchaniya" class="form-label">Дата окончания аренды *</label>
            <input type="date" id="data_okonchaniya" name="data_okonchaniya" value="{{ old('data_okonchaniya') }}" class="form-input">
            @error('data_okonchaniya')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="status_dogovora" class="form-label">Статус *</label>
            <select id="status_dogovora" name="status_dogovora" required class="form-input">
                <option value="draft" {{ old('status_dogovora') == 'draft' ? 'selected' : '' }}>Черновик</option>
                <option value="pending" {{ old('status_dogovora') == 'pending' ? 'selected' : '' }}>Ожидает подтверждения</option>
                <option value="active" {{ old('status_dogovora') == 'active' ? 'selected' : '' }}>Активен</option>
                <option value="completed" {{ old('status_dogovora') == 'completed' ? 'selected' : '' }}>Завершен</option>
                <option value="cancelled" {{ old('status_dogovora') == 'cancelled' ? 'selected' : '' }}>Отменен</option>
            </select>
            @error('status_dogovora')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-8">
            <label for="primechaniya" class="form-label">Примечания</label>
            <textarea id="primechaniya" name="primechaniya" rows="4" class="form-input">{{ old('primechaniya') }}</textarea>
            @error('primechaniya')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="divider pt-6 flex items-center justify-end gap-4">
            <a href="{{ route('admin.contracts') }}" class="btn">Отмена</a>
            <button type="submit" class="btn-primary" @if(count($propertyItems) === 0) disabled @endif>
                Создать договор
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hiddenProperty = document.getElementById('nedvizhimost_id');
    const tipSelect = document.getElementById('tip');
    const rentBlock = document.getElementById('rent_end_block_admin');
    const endInput = document.getElementById('data_okonchaniya');
    const priceLabel = document.getElementById('price_label_admin');
    const priceHint = document.getElementById('price_hint_admin');

    function syncFromProperty() {
        const op = hiddenProperty?.dataset?.operation || null;
        if (op === 'sale' || op === 'rent') {
            tipSelect.value = op;
        }
        syncRentUI();
    }

    function syncRentUI() {
        const isRent = tipSelect.value === 'rent';
        rentBlock.style.display = isRent ? 'block' : 'none';
        endInput.required = isRent;
        if (!isRent) endInput.value = '';
        if (isRent) {
            priceLabel.textContent = 'Арендная плата в месяц (₽) *';
            priceHint.textContent = 'Укажите сумму ежемесячной аренды.';
        } else {
            priceLabel.textContent = 'Цена (₽) *';
            priceHint.textContent = 'Для аренды укажите месячную плату.';
        }
    }

    hiddenProperty?.addEventListener('change', syncFromProperty);
    document.addEventListener('contract-property-selected', function(e) {
        if (e.detail?.operation) {
            hiddenProperty.dataset.operation = e.detail.operation;
            syncFromProperty();
        }
    });
    tipSelect.addEventListener('change', syncRentUI);
    syncFromProperty();
});
</script>
@endpush
@endsection
