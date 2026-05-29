{{-- Поля реквизитов документа (ручной ввод) — $tip, $values (array), $idPrefix (optional) --}}
@php
    use App\Support\DocumentDataFields;

    $fields = DocumentDataFields::fieldsForTip($tip ?? '');
    $values = $values ?? [];
    $idPrefix = $idPrefix ?? ($tip ?? 'doc');
@endphp
@if($fields !== [])
    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 mb-4">
        <p class="text-sm font-semibold text-slate-800 mb-3">Реквизиты документа</p>
        <p class="text-xs text-slate-600 mb-4">Заполните данные как в документе, затем прикрепите скан или PDF.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($fields as $key => $meta)
                @php
                    $colSpan = ($meta['cols'] ?? 1) === 2 ? 'sm:col-span-2' : '';
                    $fieldId = $idPrefix . '_' . $key;
                    $oldKey = 'dannye.' . $key;
                    $val = old($oldKey, $values[$key] ?? '');
                @endphp
                <div class="{{ $colSpan }} min-w-0">
                    <label for="{{ $fieldId }}" class="form-label">
                        {{ $meta['label'] }}
                        @if(!empty($meta['required']))
                            <span class="text-red-600">*</span>
                        @endif
                    </label>
                    @if(($meta['type'] ?? 'text') === 'date')
                        <input
                            type="date"
                            id="{{ $fieldId }}"
                            name="dannye[{{ $key }}]"
                            class="form-input w-full"
                            value="{{ $val }}"
                            @if(!empty($meta['required'])) required @endif
                        >
                    @else
                        <input
                            type="text"
                            id="{{ $fieldId }}"
                            name="dannye[{{ $key }}]"
                            class="form-input w-full {{ $key === 'kadastrovy_nomer' ? 'font-mono' : '' }}"
                            value="{{ $val }}"
                            placeholder="{{ $meta['placeholder'] ?? '' }}"
                            autocomplete="off"
                            @if(!empty($meta['required'])) required @endif
                        >
                    @endif
                    @error($oldKey)
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>
    </div>
@endif
