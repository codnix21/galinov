@php
    $property = $property ?? new \App\Models\Property();
    $show = old('tip', $property->tip ?? '') === 'commercial';
@endphp
<div id="commercial-fields-panel" class="mb-8 border border-violet-200 rounded-xl p-6 bg-violet-50/40 {{ $show ? '' : 'hidden' }}" data-commercial-form-panel>
    <h2 class="text-lg font-bold mb-1">Параметры коммерческого помещения</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
        <div>
            <label class="form-label">Назначение</label>
            <select name="tip_pomeshcheniya" class="form-input">
                <option value="">Не указано</option>
                @foreach(\App\Support\PropertyCommercialAttributes::TIP_LABELS as $value => $label)
                    <option value="{{ $value }}" {{ old('tip_pomeshcheniya', $property->tip_pomeshcheniya ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Высота потолков (м)</label>
            <input type="number" step="0.1" name="vysota_potolkov" value="{{ old('vysota_potolkov', $property->vysota_potolkov ?? '') }}" class="form-input">
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="otdelnyy_vhod" value="1" class="rounded" {{ old('otdelnyy_vhod', $property->otdelnyy_vhod ?? false) ? 'checked' : '' }}>
            <span>Отдельный вход</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="parking" value="1" class="rounded" {{ old('parking', $property->parking ?? false) ? 'checked' : '' }}>
            <span>Парковка</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="internet" value="1" class="rounded" {{ old('internet', $property->internet ?? false) ? 'checked' : '' }}>
            <span>Коммуникации</span>
        </label>
    </div>
</div>
