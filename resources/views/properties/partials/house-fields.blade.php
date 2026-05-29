{{-- Параметры частного дома — только при tip = house --}}
@php
    $property = $property ?? new \App\Models\Property();
    $tipValue = old('tip', $property->tip ?? '');
    $showHouse = $tipValue === 'house';
@endphp
<div id="house-fields-panel" class="mb-8 border border-slate-200 rounded-xl p-6 bg-slate-50/80 {{ $showHouse ? '' : 'hidden' }}" data-house-form-panel>
    <h2 class="text-lg font-bold mb-1">Параметры дома</h2>
    <p class="text-sm text-gray-600 mb-4">Заполняется только для типа «Дом». Для квартиры, коммерции и земли эти поля не используются.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
            <label for="tip_doma" class="form-label">Тип дома</label>
            <select id="tip_doma" name="tip_doma" class="form-input select-native">
                <option value="">Не указан</option>
                @foreach(\App\Support\PropertyHouseAttributes::TIP_DOMA_LABELS as $value => $label)
                    <option value="{{ $value }}" {{ old('tip_doma', $property->tip_doma ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('tip_doma')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="ploshchad_uchastka" class="form-label">Площадь участка (сотки)</label>
            <input type="number" id="ploshchad_uchastka" name="ploshchad_uchastka" value="{{ old('ploshchad_uchastka', $property->ploshchad_uchastka ?? '') }}" min="0" step="0.01" class="form-input" placeholder="18">
            @error('ploshchad_uchastka')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <p class="form-label mb-2">Удобства и коммуникации</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        @foreach(\App\Support\PropertyHouseAttributes::BOOLEAN_LABELS as $field => $label)
            <label class="flex items-center gap-2 cursor-pointer text-sm">
                <input type="checkbox" name="{{ $field }}" value="1" class="rounded border-slate-300"
                    {{ old($field, $property->{$field} ?? false) ? 'checked' : '' }}>
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>
</div>
