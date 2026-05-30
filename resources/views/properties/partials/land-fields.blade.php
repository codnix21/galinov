{{-- Параметры земельного участка — только при tip = land --}}
@php
    $property = $property ?? new \App\Models\Property();
    $tipValue = old('tip', $property->tip ?? '');
    $showLand = $tipValue === 'land';
@endphp
<div id="land-fields-panel" class="mb-8 border border-emerald-200 rounded-xl p-6 bg-emerald-50/50 {{ $showLand ? '' : 'hidden' }}" data-land-form-panel>
    <h2 class="text-lg font-bold mb-1">Параметры участка</h2>
    <p class="text-sm text-gray-600 mb-4">Заполняется для типа «Земельный участок»: площадь в сотках и подключённые коммуникации.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
            <label for="land_ploshchad_uchastka" class="form-label">Площадь участка (сотки)</label>
            <input type="number" id="land_ploshchad_uchastka" name="ploshchad_uchastka"
                   value="{{ old('ploshchad_uchastka', $property->ploshchad_uchastka ?? '') }}" min="0" step="0.01" class="form-input" placeholder="12">
            @error('ploshchad_uchastka')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <p class="form-label mb-2">Коммуникации и благоустройство</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        @foreach(\App\Support\PropertyLandAttributes::BOOLEAN_LABELS as $field => $label)
            <label class="flex items-center gap-2 cursor-pointer text-sm">
                <input type="checkbox" name="{{ $field }}" value="1" class="rounded border-slate-300"
                    {{ old($field, $property->{$field} ?? false) ? 'checked' : '' }}>
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>
</div>
