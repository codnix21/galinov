@php
    $filterPrefix = $filterPrefix ?? '';
    $fieldName = fn (string $key) => $filterPrefix !== '' ? "{$filterPrefix}[{$key}]" : $key;
    $fieldValue = fn (string $key) => $filterPrefix !== ''
        ? old("{$filterPrefix}.{$key}", request("{$filterPrefix}.{$key}"))
        : old($key, request($key));
    $isChecked = fn (string $key) => (bool) $fieldValue($key);

    $showCommercial = request('type') === 'commercial'
        || ($filterPrefix !== '' && request("{$filterPrefix}.type") === 'commercial')
        || request()->filled($filterPrefix !== '' ? "{$filterPrefix}.tip_pomeshcheniya" : 'tip_pomeshcheniya')
        || $isChecked('comm_parking') || $isChecked('comm_internet') || $isChecked('comm_otdelnyy_vhod')
        || request()->filled($filterPrefix !== '' ? "{$filterPrefix}.min_potolok" : 'min_potolok');
@endphp
<div id="catalog-commercial-filters"
     class="{{ $showCommercial ? '' : 'hidden' }} border-t border-slate-200 pt-4 mt-4"
     data-commercial-panel>
    <h3 class="text-sm font-semibold text-slate-800 mb-3">Параметры коммерции</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="form-label">Назначение</label>
            <select name="{{ $fieldName('tip_pomeshcheniya') }}" class="form-input w-full">
                <option value="">Любое</option>
                @foreach(\App\Support\PropertyCommercialAttributes::TIP_LABELS as $value => $label)
                    <option value="{{ $value }}" {{ (string) $fieldValue('tip_pomeshcheniya') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Потолки от (м)</label>
            <input type="number" step="0.1" min="0" name="{{ $fieldName('min_potolok') }}" value="{{ $fieldValue('min_potolok') }}" class="form-input">
        </div>
        <div>
            <label class="form-label">Потолки до (м)</label>
            <input type="number" step="0.1" min="0" name="{{ $fieldName('max_potolok') }}" value="{{ $fieldValue('max_potolok') }}" class="form-input">
        </div>
        @foreach(['comm_parking' => 'Парковка', 'comm_internet' => 'Коммуникации', 'comm_otdelnyy_vhod' => 'Отдельный вход'] as $key => $label)
            <div class="flex items-end min-h-[42px]">
                <label class="flex items-center gap-2 cursor-pointer py-2.5">
                    <input type="checkbox" name="{{ $fieldName($key) }}" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600"
                        {{ $isChecked($key) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">{{ $label }}</span>
                </label>
            </div>
        @endforeach
    </div>
</div>
