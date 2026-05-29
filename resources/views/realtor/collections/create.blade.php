@extends('layouts.app')

@section('title', 'Новая подборка')

@section('content')
@include('partials.realtor-nav')

<div class="max-w-2xl mx-auto card p-8">
    <h1 class="text-2xl font-bold mb-6">Создать подборку</h1>

    @if($favoritesCount > 0)
        <form method="POST" action="{{ route('realtor.collections.from-favorites') }}" class="mb-8 p-4 bg-brand-50 rounded-xl border border-brand-100">
            @csrf
            <p class="text-sm mb-3">Быстро: {{ $favoritesCount }}
                @if($favoritesCount % 10 === 1 && $favoritesCount % 100 !== 11) объект
                @elseif(in_array($favoritesCount % 10, [2, 3, 4]) && !in_array($favoritesCount % 100, [12, 13, 14])) объекта
                @else объектов
                @endif
                из избранного</p>
            <div class="mb-3">
                <label class="form-label">Название</label>
                <input type="text" name="nazvanie" class="form-input" placeholder="Например: Варианты для Ивановых" required>
            </div>
            @include('partials.fio-search-select', [
                'id' => 'klient_id_fav',
                'name' => 'klient_id',
                'label' => 'Клиент',
                'placeholder' => 'ФИО или email…',
                'required' => false,
                'searchUrl' => $clientsSearchUrl,
                'items' => $clientItems,
            ])
            <button type="submit" class="btn-primary w-full mt-2">Создать из избранного</button>
        </form>
    @endif

    <form method="POST" action="{{ route('realtor.collections.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="form-label">Название</label>
            <input type="text" name="nazvanie" class="form-input" value="{{ old('nazvanie') }}" required>
        </div>

        @include('partials.fio-search-select', [
            'id' => 'klient_id',
            'name' => 'klient_id',
            'label' => 'Клиент',
            'placeholder' => 'ФИО или email…',
            'required' => false,
            'searchUrl' => $clientsSearchUrl,
            'items' => $clientItems,
        ])
        <p class="text-xs text-gray-500 -mt-4 mb-4">
            @if(count($clientItems) === 0)
                Закреплённых клиентов пока нет — найдите по ФИО или email, клиент будет добавлен в
                <a href="{{ $clientsManageUrl }}" class="text-brand-700 underline">CRM</a>.
            @else
                Можно выбрать закреплённого или найти другого клиента по ФИО — он будет закреплён за вами.
            @endif
        </p>

        <div>
            <label class="form-label">Комментарий для клиента</label>
            <textarea name="kommentariy" class="form-input" rows="3">{{ old('kommentariy') }}</textarea>
        </div>

        <div>
            <label class="form-label" for="property_ids_select">Объекты в подборке</label>
            <p class="text-xs text-gray-500 mb-2">Выберите одно или несколько объявлений. Пустой список — добавите на странице подборки после создания.</p>
            @if($properties->isEmpty())
                <p class="text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">Нет активных объявлений в каталоге.</p>
            @else
                @php
                    $oldIds = array_map('intval', (array) old('property_ids', $favoritePropertyIds));
                @endphp
                <select id="property_ids_select" name="property_ids[]" multiple class="form-input w-full"
                    data-placeholder="Поиск: название, №, город…">
                    @foreach($properties as $p)
                        @php
                            $op = ($p->operatsiya ?? '') === 'rent' ? 'Аренда' : 'Продажа';
                            $city = $p->gorod ?? '';
                        @endphp
                        <option value="{{ $p->id }}" @selected(in_array((int) $p->id, $oldIds, true))>
                            #{{ $p->id }} — {{ $p->nazvanie }} · {{ $op }} · {{ number_format((float) $p->tsena, 0, ',', ' ') }} ₽@if($city) · {{ $city }}@endif
                        </option>
                    @endforeach
                </select>
            @endif
            @error('property_ids')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-primary w-full">Создать подборку</button>
    </form>
</div>
@endsection
