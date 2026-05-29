{{-- Фильтры каталога / заявки на подбор — только для типа «дом» --}}
@php
    $filterPrefix = $filterPrefix ?? '';
    $fieldName = fn (string $key) => $filterPrefix !== '' ? "{$filterPrefix}[{$key}]" : $key;
    $fieldValue = function (string $key) use ($filterPrefix) {
        if ($filterPrefix !== '') {
            return old("{$filterPrefix}.{$key}", request("{$filterPrefix}.{$key}"));
        }

        return old($key, request($key));
    };
    $isChecked = fn (string $key) => (bool) $fieldValue($key);

    $houseFilterKeys = ['tip_doma', 'est_tsokol', 'garazh', 'parking', 'internet', 'min_ploshchad_uchastka', 'max_ploshchad_uchastka'];
    $showHouseFilters = request('type') === 'house'
        || ($filterPrefix !== '' && request("{$filterPrefix}.type") === 'house')
        || collect($houseFilterKeys)->contains(
            fn (string $key) => request()->filled($filterPrefix !== '' ? "{$filterPrefix}.{$key}" : $key)
        );

    $amenities = [
        'est_tsokol' => 'Цокольный этаж',
        'garazh' => 'Гараж',
        'parking' => 'Парковка',
        'internet' => 'Интернет',
    ];
@endphp
<div id="catalog-house-filters"
     class="catalog-house-filters {{ $showHouseFilters ? '' : 'hidden' }} border-t border-slate-200 pt-4 mt-4"
     data-house-panel>
    <h3 class="text-sm font-semibold text-slate-800 mb-3">Параметры дома</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="min-w-0 sm:col-span-2 lg:col-span-1">
            <label class="form-label" for="catalog_tip_doma">Тип дома</label>
            <select id="catalog_tip_doma" name="{{ $fieldName('tip_doma') }}" class="form-input w-full min-w-0">
                <option value="">Любой</option>
                @foreach(\App\Support\PropertyHouseAttributes::TIP_DOMA_LABELS as $value => $label)
                    <option value="{{ $value }}" {{ (string) $fieldValue('tip_doma') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-0">
            <label class="form-label" for="catalog_min_uchastok">Участок от (сот.)</label>
            <input type="number" id="catalog_min_uchastok" name="{{ $fieldName('min_ploshchad_uchastka') }}"
                   value="{{ $fieldValue('min_ploshchad_uchastka') }}" min="0" step="0.01" class="form-input w-full" placeholder="0">
        </div>
        <div class="min-w-0">
            <label class="form-label" for="catalog_max_uchastok">Участок до (сот.)</label>
            <input type="number" id="catalog_max_uchastok" name="{{ $fieldName('max_ploshchad_uchastka') }}"
                   value="{{ $fieldValue('max_ploshchad_uchastka') }}" min="0" step="0.01" class="form-input w-full" placeholder="30">
        </div>
        @foreach($amenities as $key => $label)
            <div class="flex items-end min-h-[42px] sm:col-span-1">
                <label class="flex items-center gap-2.5 cursor-pointer py-2.5 w-full">
                    <input type="checkbox" name="{{ $fieldName($key) }}" value="1"
                           class="h-4 w-4 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30"
                           {{ $isChecked($key) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700 leading-snug">{{ $label }}</span>
                </label>
            </div>
        @endforeach
    </div>
</div>
