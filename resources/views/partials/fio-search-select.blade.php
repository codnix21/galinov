{{--
  Поисковый выбор: поле ввода + скрытое значение + выпадающий список.
  $id, $name, $label, $placeholder, $required, $searchUrl, $items [{value, label, hint?, data?}]
--}}
@php
    $fieldId = $id ?? $name;
    $selectedValue = old($name, $selected ?? '');
    $selectedItem = collect($items ?? [])->first(
        fn ($item) => (string) ($item['value'] ?? '') === (string) $selectedValue && $selectedValue !== ''
    );
    $inputLabel = $selectedItem['label'] ?? '';
@endphp
<div class="mb-6 fio-search-select" data-search-url="{{ $searchUrl ?? '' }}" data-no-search id="wrap_{{ $fieldId }}">
    <label for="{{ $fieldId }}_search" class="form-label">{{ $label }}@if(!empty($required)) *@endif</label>
    <p class="text-xs text-slate-500 mb-2">Введите ФИО, email, название или адрес — появятся подходящие варианты</p>
    <input type="hidden" name="{{ $name }}" id="{{ $fieldId }}" value="{{ $selectedValue }}" @if(!empty($required)) required @endif
        @if($selectedItem && !empty($selectedItem['data']))
            @foreach($selectedItem['data'] as $dataKey => $dataVal)
                data-{{ $dataKey }}="{{ $dataVal }}"
            @endforeach
        @endif
    >
    <input
        type="text"
        id="{{ $fieldId }}_search"
        class="form-input fio-search-input"
        placeholder="{{ $placeholder ?? 'Начните вводить для поиска…' }}"
        value="{{ $inputLabel }}"
        autocomplete="off"
        role="combobox"
        aria-expanded="false"
        aria-controls="{{ $fieldId }}_listbox"
    >
    <ul
        id="{{ $fieldId }}_listbox"
        class="fio-search-results hidden absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg text-sm"
        role="listbox"
    ></ul>
    <script type="application/json" class="fio-search-initial">@json($items ?? [])</script>
    @error($name)
        <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
    @enderror
</div>
@include('partials.fio-search-scripts')
