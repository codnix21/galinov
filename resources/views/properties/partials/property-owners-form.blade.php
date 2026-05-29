@php
    $property->loadMissing(['owners.user']);
    \App\Services\PropertyOwnersService::ensureDefaultOwner($property);
    $property->load('owners.user');
    $ownerRows = old('owners');
    if (!is_array($ownerRows) || $ownerRows === []) {
        $ownerRows = $property->owners->map(fn ($o) => [
            'polzovatel_id' => $o->polzovatel_id,
            'dolya_procent' => $o->dolya_procent,
            'osnovnoy' => $o->osnovnoy ? '1' : '',
            'label' => $o->fio(),
        ])->values()->all();
    }
    if ($ownerRows === []) {
        $ownerRows = [['polzovatel_id' => '', 'dolya_procent' => '100', 'osnovnoy' => '1', 'label' => '']];
    }
    $usersSearchUrl = route('api.contracts.search.clients');
@endphp
<div class="card p-6 mb-8" id="sobstvenniki">
    <h2 class="text-xl font-bold mb-2">Собственники недвижимости</h2>
    <p class="text-sm text-gray-600 mb-4">
        Укажите всех владельцев и доли (сумма = 100 %). Основной собственник подписывает договор первым; в договор попадают все продавцы.
    </p>

    @error('owners')
        <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route('properties.owners.update', $property) }}" id="owners-form">
        @csrf
        @method('PUT')
        <div id="owners-list" class="space-y-4 mb-4">
            @foreach($ownerRows as $index => $row)
                @include('properties.partials.property-owner-row', [
                    'index' => $index,
                    'row' => $row,
                    'usersSearchUrl' => $usersSearchUrl,
                ])
            @endforeach
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="button" id="add-owner-row" class="btn">+ Добавить собственника</button>
            <button type="submit" class="btn-primary">Сохранить собственников</button>
        </div>
    </form>
</div>

<template id="owner-row-template">
    @include('properties.partials.property-owner-row', [
        'index' => '__INDEX__',
        'row' => ['polzovatel_id' => '', 'dolya_procent' => '', 'osnovnoy' => '', 'label' => ''],
        'usersSearchUrl' => $usersSearchUrl,
    ])
</template>

@push('scripts')
<script>
(function() {
    const list = document.getElementById('owners-list');
    const tpl = document.getElementById('owner-row-template');
    const addBtn = document.getElementById('add-owner-row');
    if (!list || !tpl || !addBtn) return;

    let nextIndex = list.querySelectorAll('[data-owner-row]').length;

    addBtn.addEventListener('click', function() {
        const html = tpl.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        list.appendChild(wrap.firstElementChild);
        if (window.initFioSearchSelects) {
            window.initFioSearchSelects();
        }
    });

    list.addEventListener('change', function(e) {
        if (e.target.name && e.target.name.endsWith('[osnovnoy]')) {
            list.querySelectorAll('input[name$="[osnovnoy]"]').forEach(function(r) {
                if (r !== e.target) r.checked = false;
            });
        }
    });

    list.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-owner-row')) {
            const rows = list.querySelectorAll('[data-owner-row]');
            if (rows.length <= 1) {
                alert('Должен остаться хотя бы один собственник.');
                return;
            }
            e.target.closest('[data-owner-row]').remove();
        }
    });
})();
</script>
@include('partials.fio-search-scripts')
@endpush
