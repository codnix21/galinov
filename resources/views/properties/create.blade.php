{{-- Форма добавления нового объявления. --}}
@extends('layouts.app')

@section('title', 'Создать объявление')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Создать объявление</h1>
        <p class="text-gray-600">После сохранения черновика вы загрузите документы сами — набор зависит от типа объекта и сделки (продажа или аренда). Без проверки публикация недоступна.</p>
    </div>

    <form method="POST" action="{{ route('properties.store') }}" class="card p-8" enctype="multipart/form-data">
        @csrf

        @if(!empty($showListingAuthor))
            @include('properties.partials.listing-author-fields', [
                'listingAuthorOptions' => $listingAuthorOptions,
                'clientsSearchUrl' => $clientsSearchUrl,
                'clientItems' => $clientItems,
            ])
        @endif

        <div class="mb-6">
            <label for="nazvanie" class="form-label">Название *</label>
            <input type="text" id="nazvanie" name="nazvanie" value="{{ old('nazvanie') }}" required class="form-input">
            @error('nazvanie')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="opisanie" class="form-label">Описание *</label>
            <textarea id="opisanie" name="opisanie" rows="6" required class="form-input">{{ old('opisanie') }}</textarea>
            @error('opisanie')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="tip" class="form-label">Тип *</label>
                <select id="tip" name="tip" required class="form-input select-native">
                    <option value="">Выберите тип</option>
                    @foreach(\App\Models\Property::tipNazvaniya() as $value => $label)
                        <option value="{{ $value }}" {{ old('tip') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('tip')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="operatsiya" class="form-label">Операция *</label>
                <select id="operatsiya" name="operatsiya" required class="form-input select-native">
                    <option value="sale" {{ old('operatsiya', 'sale') == 'sale' ? 'selected' : '' }}>Продажа</option>
                    <option value="rent" {{ old('operatsiya') == 'rent' ? 'selected' : '' }}>Аренда</option>
                </select>
                @error('operatsiya')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mb-6">
            <label for="tsena" class="form-label">Цена (₽) *</label>
            <input type="number" id="tsena" name="tsena" value="{{ old('tsena') }}" step="0.01" min="0" required class="form-input">
            @error('tsena')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="gorod" class="form-label">Город *</label>
                <input type="text" id="gorod" name="gorod" value="{{ old('gorod') }}" required class="form-input dadata-city" data-url="{{ route('api.dadata.city') }}" placeholder="Например: Иркутск" autocomplete="off">
                @error('gorod')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Подсказки только по населённым пунктам. Выберите город из списка — так адрес на карте привяжется к региону.</p>
            </div>
            <div>
                <label for="adres_ulitsy" class="form-label">Улица и дом *</label>
                <input type="text" id="adres_ulitsy" name="adres_ulitsy" value="{{ old('adres_ulitsy') }}" required class="form-input dadata-address" data-url="{{ route('api.dadata.address') }}" placeholder="ул. Ленина, д. 1" autocomplete="off">
                @error('adres_ulitsy')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Сначала укажите город. Здесь — только улица и номер дома (без названия города). Выберите подсказку, чтобы сохранить точку на карте.</p>
            </div>
        </div>
        <input type="hidden" name="geo_shirota" value="{{ old('geo_shirota') }}">
        <input type="hidden" name="geo_dolgota" value="{{ old('geo_dolgota') }}">

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="ploshchad" class="form-label">Площадь (м²)</label>
                <input type="number" id="ploshchad" name="ploshchad" value="{{ old('ploshchad') }}" min="0" class="form-input">
                @error('ploshchad')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="komnaty" class="form-label">Количество комнат</label>
                <input type="number" id="komnaty" name="komnaty" value="{{ old('komnaty') }}" min="0" class="form-input">
                @error('komnaty')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="etazh" class="form-label">Этаж</label>
                <input type="number" id="etazh" name="etazh" value="{{ old('etazh') }}" min="1" class="form-input">
                @error('etazh')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="vsego_etazhey" class="form-label">Всего этажей</label>
                <input type="number" id="vsego_etazhey" name="vsego_etazhey" value="{{ old('vsego_etazhey') }}" min="1" class="form-input">
                @error('vsego_etazhey')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <p class="text-xs text-gray-500 -mt-4 mb-6">Этаж не может быть больше общего количества этажей в доме.</p>

        @include('properties.partials.house-fields')

        <div class="mb-8">
            <label for="images" class="form-label">Фотографии</label>
            <input type="file" id="images" name="images[]" multiple accept="image/*" class="form-input">
            <p class="mt-2 text-sm text-gray-600">Можно выбрать несколько фотографий (максимум 10)</p>
            @error('images')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('images.*')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="status_obyavleniya" class="form-label">Статус *</label>
            <select id="status_obyavleniya" name="status_obyavleniya" required class="form-input select-native">
                <option value="draft" {{ old('status_obyavleniya', 'draft') == 'draft' ? 'selected' : '' }}>Черновик</option>
                <option value="active" {{ old('status_obyavleniya') == 'active' ? 'selected' : '' }}>Отправить на модерацию</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Перед каталогом объявление проходит модерацию. Ненормативная лексика и слова из словаря запрета в названии и описании не допускаются — сохранение будет отклонено до исправления текста.</p>
            @error('status_obyavleniya')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-sm text-gray-600">Вы можете сохранить объявление как черновик и опубликовать его позже</p>
        </div>

        <div class="divider pt-6 flex items-center justify-end gap-4">
            <a href="{{ route('properties.index') }}" class="btn">
                Отмена
            </a>
            <button type="submit" class="btn-primary">
                Сохранить
            </button>
        </div>
    </form>
</div>

@include('properties.partials.floor-fields-validation')
@endsection
