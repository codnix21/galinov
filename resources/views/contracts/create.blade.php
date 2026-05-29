{{-- Создание нового договора. --}}
@extends('layouts.app')

@section('title', 'Новый договор')

@section('content')
@php
    $isRealtorForm = Auth::user()->isRealtor() || Auth::user()->isAdmin();
    $myId = (string) Auth::id();
@endphp
<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Новый договор</h1>
        @if($isRealtorForm)
            <p class="text-gray-600">Укажите объект, владельца, покупателя и условия. После создания подтверждение нужно от владельца и покупателя.</p>
        @else
            <p class="text-gray-600">Выберите объект, риэлтора и вторую сторону. Подтвердить договор должны риэлтор и вторая сторона (не вы).</p>
        @endif
    </div>

    @include('partials.help-hint', [
        'title' => 'Справка',
        'points' => [
            'Сторона 1 — владелец объекта недвижимости, сторона 2 — покупатель (или арендатор при аренде).',
            'Риэлтор ведёт сделку и указан в договоре отдельно.',
            $isRealtorForm
                ? 'Если договор создаёте вы как риэлтор, подтверждают владелец и покупатель.'
                : 'Если создаёте вы как клиент, подтверждают риэлтор и вторая сторона сделки.',
            'Для аренды после активации распечатайте бланк и загрузите скан подписанного документа.',
        ],
    ])

    @if(count($propertyItems) === 0)
        <div class="mb-6 p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-sm">
            Нет активных объектов для договора. Опубликуйте объявление в каталоге или дождитесь одобрения модерации.
            <a href="{{ route('properties.index') }}" class="underline font-medium ml-1">Перейти в каталог</a>
        </div>
    @endif

    <form method="POST" action="{{ route('contracts.store') }}" class="card p-8" id="contract_create_form">
        @csrf

        @include('partials.fio-search-select', [
            'id' => 'nedvizhimost_id',
            'name' => 'nedvizhimost_id',
            'label' => 'Объект недвижимости',
            'required' => true,
            'placeholder' => 'Поиск по названию, городу или адресу…',
            'searchUrl' => route('api.contracts.search.properties'),
            'selected' => old('nedvizhimost_id', $property?->id),
            'items' => $propertyItems,
        ])

        @if(!$isRealtorForm)
            <div class="mb-6">
                <span class="form-label block mb-2">Ваша роль в сделке *</span>
                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="client_party" value="owner" class="rounded border-slate-300"
                            {{ old('client_party', 'owner') === 'owner' ? 'checked' : '' }}>
                        <span>Я владелец объекта</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="client_party" value="buyer" class="rounded border-slate-300"
                            {{ old('client_party') === 'buyer' ? 'checked' : '' }}>
                        <span>Я покупатель (арендатор)</span>
                    </label>
                </div>
                @error('client_party')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            @include('partials.fio-search-select', [
                'id' => 'rieltor_id',
                'name' => 'rieltor_id',
                'label' => 'Риэлтор, ведущий сделку',
                'required' => true,
                'placeholder' => 'Поиск по ФИО или email…',
                'searchUrl' => route('api.contracts.search.realtors'),
                'selected' => old('rieltor_id'),
                'items' => $realtorItems,
            ])
        @else
            <input type="hidden" name="rieltor_id" value="{{ Auth::user()->getKey() }}">
        @endif

        @include('partials.fio-search-select', [
            'id' => 'vladelets_id',
            'name' => 'vladelets_id',
            'label' => 'Сторона 1: владелец объекта',
            'required' => true,
            'placeholder' => 'Поиск по ФИО или email…',
            'searchUrl' => route('api.contracts.search.clients'),
            'selected' => old('vladelets_id', $defaultOwnerId),
            'items' => $clientItems,
        ])

        @include('partials.fio-search-select', [
            'id' => 'pokupatel_id',
            'name' => 'pokupatel_id',
            'label' => 'Сторона 2: покупатель объекта',
            'required' => true,
            'placeholder' => 'Поиск по ФИО или email…',
            'searchUrl' => route('api.contracts.search.clients'),
            'selected' => old('pokupatel_id'),
            'items' => $clientItems,
        ])

        @if($isRealtorForm)
            <p class="text-sm text-gray-500 -mt-4 mb-6">Риэлтор сделки: {{ Auth::user()->familia }} {{ Auth::user()->imya }}</p>
        @endif

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="tsena" class="form-label"><span id="price_label">Сумма / цена (₽) *</span></label>
                <input type="number" id="tsena" name="tsena" value="{{ old('tsena') }}" step="0.01" min="0" required class="form-input">
                <p class="mt-1 text-xs text-gray-500" id="price_hint">Для аренды укажите месячную плату.</p>
                @error('tsena')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="data_nachala" class="form-label">Дата начала *</label>
                <input type="date" id="data_nachala" name="data_nachala" value="{{ old('data_nachala') }}" required class="form-input">
                @error('data_nachala')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mb-6" id="rent_end_block" style="display: none;">
            <label for="data_okonchaniya" class="form-label">Дата окончания аренды *</label>
            <input type="date" id="data_okonchaniya" name="data_okonchaniya" value="{{ old('data_okonchaniya') }}" class="form-input">
            @error('data_okonchaniya')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-8">
            <label for="primechaniya" class="form-label">Примечания</label>
            <textarea id="primechaniya" name="primechaniya" rows="4" class="form-input" placeholder="Дополнительная информация о договоре...">{{ old('primechaniya') }}</textarea>
            @error('primechaniya')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        @error('vladelets_id')
            <p class="mb-4 text-sm text-red-600 font-medium">{{ $message }}</p>
        @enderror
        @error('pokupatel_id')
            <p class="mb-4 text-sm text-red-600 font-medium">{{ $message }}</p>
        @enderror
        @error('rieltor_id')
            <p class="mb-4 text-sm text-red-600 font-medium">{{ $message }}</p>
        @enderror

        <div class="divider pt-6 flex items-center justify-end gap-4">
            <a href="{{ route('contracts.index') }}" class="btn">
                Отмена
            </a>
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
    const ownerHidden = document.getElementById('vladelets_id');
    const buyerHidden = document.getElementById('pokupatel_id');
    const rentBlock = document.getElementById('rent_end_block');
    const endInput = document.getElementById('data_okonchaniya');
    const priceLabel = document.getElementById('price_label');
    const priceHint = document.getElementById('price_hint');
    const myId = @json($myId);
    const isClientForm = @json(!$isRealtorForm);

    function setHiddenUser(fieldId, userId) {
        const wrap = document.getElementById(fieldId)?.closest('.fio-search-select');
        if (!wrap || !userId) return;
        const hidden = document.getElementById(fieldId);
        const input = wrap.querySelector('.fio-search-input');
        const initial = wrap.querySelector('.fio-search-initial');
        let items = [];
        try { items = JSON.parse(initial?.textContent || '[]'); } catch (e) {}
        const item = items.find(i => String(i.value) === String(userId));
        if (item && hidden && input) {
            hidden.value = item.value;
            input.value = item.label;
        }
    }

    function applyClientPartyLocks() {
        if (!isClientForm) return;
        const party = document.querySelector('input[name="client_party"]:checked')?.value;
        if (party === 'owner') {
            setHiddenUser('vladelets_id', myId);
        } else if (party === 'buyer') {
            setHiddenUser('pokupatel_id', myId);
        }
    }

    function onPropertySelected(detail) {
        if (detail?.operation) {
            hiddenProperty.dataset.operation = detail.operation;
        }
        if (detail?.owner_id && ownerHidden && !ownerHidden.value) {
            setHiddenUser('vladelets_id', detail.owner_id);
        }
        syncOperationUI();
    }

    function syncOperationUI() {
        const op = hiddenProperty?.dataset?.operation || null;
        const isRent = op === 'rent';
        rentBlock.style.display = isRent ? 'block' : 'none';
        endInput.required = isRent;
        if (!isRent) {
            endInput.value = '';
        }
        if (isRent) {
            priceLabel.textContent = 'Арендная плата в месяц (₽) *';
            priceHint.textContent = 'Укажите сумму ежемесячной аренды.';
        } else {
            priceLabel.textContent = 'Цена договора (₽) *';
            priceHint.textContent = 'Укажите цену сделки.';
        }
    }

    hiddenProperty?.addEventListener('change', syncOperationUI);
    document.addEventListener('contract-property-selected', (e) => onPropertySelected(e.detail || {}));
    document.querySelectorAll('input[name="client_party"]').forEach(r => {
        r.addEventListener('change', applyClientPartyLocks);
    });

    applyClientPartyLocks();
    syncOperationUI();
});
</script>
@endpush
@endsection
