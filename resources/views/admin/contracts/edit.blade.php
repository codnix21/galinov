{{-- Редактирование договора. --}}
@extends('layouts.app')

@section('title', 'Редактировать договор')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Редактировать договор</h1>
        <p class="text-gray-600">Тип договора должен совпадать с выбранным объявлением.</p>
        @if(($contract->tip ?? $contract->type) === 'rent')
            <p class="text-sm text-gray-500 mt-2">Для аренды: <a href="{{ route('contracts.print-rent', $contract) }}" target="_blank" class="text-blue-600 hover:underline">типовой бланк для печати</a> (как у пользователя). После подписания можно прикрепить скан ниже или в кабинете клиента/риэлтора.</p>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.contracts.update', $contract) }}" class="card p-8" id="admin_contract_form_edit" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <label for="nedvizhimost_id" class="form-label">Объект недвижимости *</label>
            <select id="nedvizhimost_id" name="nedvizhimost_id" required class="form-input">
                @foreach($properties as $property)
                    @php $op = $property->operatsiya ?? $property->operation; @endphp
                    <option value="{{ $property->id }}" data-operation="{{ $op }}" {{ old('nedvizhimost_id', $contract->nedvizhimost_id ?? $contract->property_id) == $property->id ? 'selected' : '' }}>
                        [{{ $op === 'rent' ? 'Аренда' : 'Продажа' }}] {{ $property->nazvanie ?? $property->title }} — {{ number_format($property->tsena ?? $property->price, 0, ',', ' ') }} ₽
                    </option>
                @endforeach
            </select>
            @error('nedvizhimost_id')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="vladelets_id" class="form-label">Владелец объекта *</label>
                <select id="vladelets_id" name="vladelets_id" required class="form-input">
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('vladelets_id', $contract->vladelets_id) == $client->id ? 'selected' : '' }}>
                            {{ $client->name }} ({{ $client->email }})
                        </option>
                    @endforeach
                </select>
                @error('vladelets_id')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="pokupatel_id" class="form-label">Покупатель *</label>
                <select id="pokupatel_id" name="pokupatel_id" required class="form-input">
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('pokupatel_id', $contract->pokupatel_id ?? $contract->klient_id) == $client->id ? 'selected' : '' }}>
                            {{ $client->name }} ({{ $client->email }})
                        </option>
                    @endforeach
                </select>
                @error('pokupatel_id')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mb-6">
                <label for="rieltor_id" class="form-label">Риэлтор сделки *</label>
                <select id="rieltor_id" name="rieltor_id" required class="form-input">
                    @foreach($realtors as $realtor)
                        <option value="{{ $realtor->id }}" {{ old('rieltor_id', $contract->rieltor_id ?? $contract->realtor_id) == $realtor->id ? 'selected' : '' }}>
                            {{ $realtor->name }} ({{ $realtor->email }})
                        </option>
                    @endforeach
                </select>
                @error('rieltor_id')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="tip" class="form-label">Тип договора *</label>
                <select id="tip" name="tip" required class="form-input">
                    <option value="sale" {{ old('tip', $contract->tip ?? $contract->type) == 'sale' ? 'selected' : '' }}>Купля-продажа</option>
                    <option value="rent" {{ old('tip', $contract->tip ?? $contract->type) == 'rent' ? 'selected' : '' }}>Аренда</option>
                </select>
                @error('tip')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="tsena" class="form-label"><span id="price_label_admin">Цена (₽) *</span></label>
                <input type="number" id="tsena" name="tsena" value="{{ old('tsena', $contract->tsena ?? $contract->price) }}" step="0.01" min="0" required class="form-input">
                <p class="mt-1 text-xs text-gray-500" id="price_hint_admin">Для аренды укажите месячную плату.</p>
                @error('tsena')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mb-6">
            <label for="data_nachala" class="form-label">Дата начала *</label>
            <input type="date" id="data_nachala" name="data_nachala" value="{{ old('data_nachala', ($contract->data_nachala ?? $contract->start_date)?->format('Y-m-d')) }}" required class="form-input">
            @error('data_nachala')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6" id="rent_end_block_admin">
            <label for="data_okonchaniya" class="form-label">Дата окончания аренды *</label>
            <input type="date" id="data_okonchaniya" name="data_okonchaniya" value="{{ old('data_okonchaniya', ($contract->data_okonchaniya ?? $contract->end_date)?->format('Y-m-d')) }}" class="form-input">
            @error('data_okonchaniya')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="status_dogovora" class="form-label">Статус *</label>
            <select id="status_dogovora" name="status_dogovora" required class="form-input">
                <option value="draft" {{ old('status_dogovora', $contract->status_dogovora ?? $contract->status) == 'draft' ? 'selected' : '' }}>Черновик</option>
                <option value="pending" {{ old('status_dogovora', $contract->status_dogovora ?? $contract->status) == 'pending' ? 'selected' : '' }}>Ожидает подтверждения</option>
                <option value="active" {{ old('status_dogovora', $contract->status_dogovora ?? $contract->status) == 'active' ? 'selected' : '' }}>Активен</option>
                <option value="completed" {{ old('status_dogovora', $contract->status_dogovora ?? $contract->status) == 'completed' ? 'selected' : '' }}>Завершен</option>
                <option value="cancelled" {{ old('status_dogovora', $contract->status_dogovora ?? $contract->status) == 'cancelled' ? 'selected' : '' }}>Отменен</option>
            </select>
            @error('status_dogovora')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-8">
            <label for="primechaniya" class="form-label">Примечания</label>
            <textarea id="primechaniya" name="primechaniya" rows="4" class="form-input">{{ old('primechaniya', $contract->primechaniya ?? $contract->notes) }}</textarea>
            @error('primechaniya')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-8 p-4 rounded-lg border border-slate-200 bg-slate-50" id="admin_scan_block">
            <p class="font-medium text-gray-900 mb-1">Скан подписанного договора (все стороны)</p>
            <p class="text-sm text-gray-600 mb-3">PDF, JPG, PNG, WEBP, до 10 МБ. Файл видят владелец, покупатель и риэлтор. Риэлтор может загрузить скан со страницы договора.</p>
            @if($contract->skan_dogovora)
                <p class="text-sm mb-2"><a href="{{ $contract->skan_dogovora_url }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">Текущий файл</a></p>
            @endif
            <input type="file" id="skan_dogovora" name="skan_dogovora" accept=".pdf,.jpg,.jpeg,.png,.webp" class="form-input">
            @error('skan_dogovora')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="divider pt-6 flex items-center justify-end gap-4">
            <a href="{{ route('admin.contracts') }}" class="btn">
                Отмена
            </a>
            <button type="submit" class="btn-primary">
                Сохранить изменения
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const propSelect = document.getElementById('nedvizhimost_id');
    const tipSelect = document.getElementById('tip');
    const rentBlock = document.getElementById('rent_end_block_admin');
    const endInput = document.getElementById('data_okonchaniya');
    const priceLabel = document.getElementById('price_label_admin');
    const priceHint = document.getElementById('price_hint_admin');

    function syncFromProperty() {
        const opt = propSelect.options[propSelect.selectedIndex];
        const op = opt ? opt.getAttribute('data-operation') : null;
        if (op === 'sale' || op === 'rent') {
            tipSelect.value = op;
        }
        syncRentUI();
    }

    function syncRentUI() {
        const isRent = tipSelect.value === 'rent';
        rentBlock.style.display = isRent ? 'block' : 'none';
        endInput.required = isRent;
        if (!isRent) {
            endInput.value = '';
        }
        if (isRent) {
            priceLabel.textContent = 'Арендная плата в месяц (₽) *';
            priceHint.textContent = 'Укажите сумму ежемесячной аренды.';
        } else {
            priceLabel.textContent = 'Цена (₽) *';
            priceHint.textContent = 'Для аренды укажите месячную плату.';
        }
    }

    propSelect.addEventListener('change', syncFromProperty);
    tipSelect.addEventListener('change', syncRentUI);
    syncRentUI();
});
</script>
@endsection
