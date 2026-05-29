@php
    $index = $index ?? 0;
    $row = $row ?? [];
    $uid = old("owners.{$index}.polzovatel_id", $row['polzovatel_id'] ?? '');
    $share = old("owners.{$index}.dolya_procent", $row['dolya_procent'] ?? '');
    $isMain = old("owners.{$index}.osnovnoy", $row['osnovnoy'] ?? '') ? true : false;
    $label = $row['label'] ?? '';
    if ($uid && $label === '' && !empty($row['user'])) {
        $label = trim($row['user']->familia.' '.$row['user']->imya);
    }
@endphp
<div class="p-4 border border-slate-200 rounded-lg bg-white" data-owner-row>
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
        <div class="md:col-span-6">
            <label class="form-label">Собственник</label>
            <div class="fio-search-select relative isolate" data-search-url="{{ $usersSearchUrl }}">
                <input type="hidden" name="owners[{{ $index }}][polzovatel_id]" value="{{ $uid }}">
                <input type="text" class="form-input fio-search-input" value="{{ $label }}" placeholder="ФИО или email" autocomplete="off" aria-expanded="false" aria-autocomplete="list">
                <ul class="fio-search-results absolute z-30 left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-52 overflow-y-auto hidden"></ul>
                <script type="application/json" class="fio-search-initial">@json($uid && $label ? [['value' => (string) $uid, 'label' => $label, 'hint' => '']] : [])</script>
            </div>
            @error("owners.{$index}.polzovatel_id")
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="md:col-span-3">
            <label class="form-label">Доля, %</label>
            <input type="number" name="owners[{{ $index }}][dolya_procent]" value="{{ $share }}" min="0.01" max="100" step="0.01" class="form-input" required>
            @error("owners.{$index}.dolya_procent")
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="md:col-span-2">
            <label class="flex items-center gap-2 cursor-pointer text-sm mt-6 md:mt-0">
                <input type="checkbox" name="owners[{{ $index }}][osnovnoy]" value="1" class="rounded border-slate-300" {{ $isMain ? 'checked' : '' }}>
                <span>Основной</span>
            </label>
        </div>
        <div class="md:col-span-1 flex md:justify-end">
            <button type="button" class="text-sm text-red-600 hover:underline remove-owner-row mt-6 md:mt-0">Удалить</button>
        </div>
    </div>
</div>
