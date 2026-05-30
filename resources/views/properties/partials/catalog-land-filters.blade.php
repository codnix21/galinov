{{-- Фильтры каталога — земельный участок --}}
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

    $landFilterKeys = [
        'min_ploshchad_uchastka', 'max_ploshchad_uchastka',
        'land_gaz', 'land_voda', 'land_kanal', 'land_internet', 'land_zabor', 'land_okhrana',
    ];
    $showLandFilters = request('type') === 'land'
        || ($filterPrefix !== '' && request("{$filterPrefix}.type") === 'land')
        || collect($landFilterKeys)->contains(
            fn (string $key) => request()->filled($filterPrefix !== '' ? "{$filterPrefix}.{$key}" : $key)
                || ($key !== 'min_ploshchad_uchastka' && $key !== 'max_ploshchad_uchastka' && $isChecked($key))
        );

    $amenities = [
        'land_gaz' => 'Газ',
        'land_voda' => 'Водоснабжение',
        'land_kanal' => 'Канализация',
        'land_internet' => 'Электричество / связь',
        'land_zabor' => 'Ограждение',
        'land_okhrana' => 'Охрана',
    ];
@endphp
<div id="catalog-land-filters"
     class="catalog-land-filters {{ $showLandFilters ? '' : 'hidden' }} border-t border-slate-200 pt-4 mt-4"
     data-land-panel>
    <h3 class="text-sm font-semibold text-slate-800 mb-3">Параметры земельного участка</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="min-w-0">
            <label class="form-label" for="catalog_land_min_uchastok">Площадь от (сот.)</label>
            <input type="number" id="catalog_land_min_uchastok" name="{{ $fieldName('min_ploshchad_uchastka') }}"
                   value="{{ $fieldValue('min_ploshchad_uchastka') }}" min="0" step="0.01" class="form-input w-full" placeholder="6">
        </div>
        <div class="min-w-0">
            <label class="form-label" for="catalog_land_max_uchastok">Площадь до (сот.)</label>
            <input type="number" id="catalog_land_max_uchastok" name="{{ $fieldName('max_ploshchad_uchastka') }}"
                   value="{{ $fieldValue('max_ploshchad_uchastka') }}" min="0" step="0.01" class="form-input w-full" placeholder="50">
        </div>
        @foreach($amenities as $key => $label)
            <div class="flex items-end min-h-[42px]">
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
