{{-- Выбор, от чьего имени риэлтор размещает объявление. --}}
@php
    $listingMode = old('listing_mode', $defaultMode ?? \App\Support\PropertyListingAuthor::REALTOR_FOR_CLIENT);
@endphp
<div class="mb-8 p-5 rounded-xl border border-brand-200 bg-brand-50/50" id="listing-author-block">
    <h2 class="text-lg font-bold mb-1">От чьего имени объявление</h2>
    <p class="text-sm text-gray-600 mb-4">Укажите, кто собственник в системе и кто ведёт публикацию.</p>

    <div class="space-y-3 mb-4">
        @foreach($listingAuthorOptions as $value => $label)
            <label class="flex items-start gap-3 text-sm cursor-pointer rounded-lg p-3 bg-white border border-slate-200 has-[:checked]:border-brand-400 has-[:checked]:ring-1 has-[:checked]:ring-brand-200">
                <input type="radio" name="listing_mode" value="{{ $value }}" class="mt-1 listing-mode-radio"
                    {{ $listingMode === $value ? 'checked' : '' }}>
                <span>
                    <strong>{{ $label }}</strong>
                    <span class="block text-gray-600 text-xs mt-0.5">{{ \App\Support\PropertyListingAuthor::description($value) }}</span>
                </span>
            </label>
        @endforeach
    </div>

    <div id="listing-client-picker" class="{{ $listingMode === \App\Support\PropertyListingAuthor::REALTOR_FOR_CLIENT ? '' : 'hidden' }}">
        @include('partials.fio-search-select', [
            'id' => 'vladelets_id',
            'name' => 'vladelets_id',
            'label' => 'Клиент-собственник',
            'placeholder' => 'ФИО или email клиента…',
            'required' => false,
            'searchUrl' => $clientsSearchUrl,
            'items' => $clientItems ?? [],
        ])
        <p class="text-xs text-gray-500 -mt-2">Клиент будет закреплён за вами в CRM. Документы на объект — от его имени, загрузить можете вы.</p>
    </div>

    @error('listing_mode')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error('vladelets_id')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<script>
(function () {
    var block = document.getElementById('listing-author-block');
    if (!block) return;
    var picker = document.getElementById('listing-client-picker');
    var forClient = @json(\App\Support\PropertyListingAuthor::REALTOR_FOR_CLIENT);
    function sync() {
        var mode = block.querySelector('input[name="listing_mode"]:checked');
        var show = mode && mode.value === forClient;
        if (picker) picker.classList.toggle('hidden', !show);
        var hidden = picker && picker.querySelector('input[type="hidden"][name="vladelets_id"]');
        if (hidden) {
            if (show) {
                hidden.removeAttribute('disabled');
            } else {
                hidden.setAttribute('disabled', 'disabled');
            }
        }
    }
    block.querySelectorAll('.listing-mode-radio').forEach(function (el) {
        el.addEventListener('change', sync);
    });
    sync();
})();
</script>
