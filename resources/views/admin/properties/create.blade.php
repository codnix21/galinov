{{-- Админ создаёт объявление. --}}
@extends('layouts.app')

@section('title', 'Создать объявление')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Создать объявление</h1>
        <p class="text-gray-600">Заполните форму для создания нового объявления</p>
    </div>

    <form method="POST" action="{{ route('admin.properties.store') }}" class="card p-8" enctype="multipart/form-data">
        @csrf

        <div class="mb-6">
            <label for="nazvanie" class="form-label">Название *</label>
            <input type="text" id="nazvanie" name="nazvanie" value="{{ old('nazvanie') }}" required class="form-input">
            @error('nazvanie')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="opisanie" class="form-label">Описание *</label>
            <textarea id="opisanie" name="opisanie" rows="6" required class="form-input">{{ old('opisanie') }}</textarea>
            @error('opisanie')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
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
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="operatsiya" class="form-label">Операция *</label>
                <select id="operatsiya" name="operatsiya" required class="form-input select-native">
                    <option value="sale" {{ old('operatsiya', 'sale') == 'sale' ? 'selected' : '' }}>Продажа</option>
                    <option value="rent" {{ old('operatsiya') == 'rent' ? 'selected' : '' }}>Аренда</option>
                </select>
                @error('operatsiya')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mb-6">
            <label for="tsena" class="form-label">Цена (₽) *</label>
            <input type="number" id="tsena" name="tsena" value="{{ old('tsena') }}" step="0.01" min="0" required class="form-input">
            @error('tsena')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="gorod" class="form-label">Город *</label>
                <input type="text" id="gorod" name="gorod" value="{{ old('gorod') }}" required class="form-input dadata-city" data-url="{{ route('api.dadata.city') }}" placeholder="Например: Иркутск" autocomplete="off">
                @error('gorod')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Подсказки только по городам и населённым пунктам.</p>
            </div>
            <div>
                <label for="adres_ulitsy" class="form-label">Улица и дом *</label>
                <input type="text" id="adres_ulitsy" name="adres_ulitsy" value="{{ old('adres_ulitsy') }}" required class="form-input dadata-address" data-url="{{ route('api.dadata.address') }}" placeholder="ул. Ленина, д. 1" autocomplete="off">
                @error('adres_ulitsy')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Сначала город. Здесь — улица и дом без города; выберите подсказку для координат.</p>
            </div>
        </div>
        <input type="hidden" name="geo_shirota" value="{{ old('geo_shirota') }}">
        <input type="hidden" name="geo_dolgota" value="{{ old('geo_dolgota') }}">

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="ploshchad" class="form-label">Площадь (м²)</label>
                <input type="number" id="ploshchad" name="ploshchad" value="{{ old('ploshchad') }}" min="0" class="form-input">
                @error('ploshchad')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="komnaty" class="form-label">Количество комнат</label>
                <input type="number" id="komnaty" name="komnaty" value="{{ old('komnaty') }}" min="0" class="form-input">
                @error('komnaty')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="etazh" class="form-label">Этаж</label>
                <input type="number" id="etazh" name="etazh" value="{{ old('etazh') }}" class="form-input">
                @error('etazh')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="vsego_etazhey" class="form-label">Всего этажей</label>
                <input type="number" id="vsego_etazhey" name="vsego_etazhey" value="{{ old('vsego_etazhey') }}" min="0" class="form-input">
                @error('vsego_etazhey')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        @include('properties.partials.house-fields')

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="polzovatel_id" class="form-label">Риэлтор *</label>
                <select id="polzovatel_id" name="polzovatel_id" required class="form-input">
                    <option value="">Выберите риэлтора</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('polzovatel_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('polzovatel_id')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status_obyavleniya" class="form-label">Статус *</label>
                <select id="status_obyavleniya" name="status_obyavleniya" required class="form-input select-native">
                    <option value="draft" {{ old('status_obyavleniya') == 'draft' ? 'selected' : '' }}>Черновик</option>
                    <option value="pending_review" {{ old('status_obyavleniya') == 'pending_review' ? 'selected' : '' }}>На модерации</option>
                    <option value="active" {{ old('status_obyavleniya', 'active') == 'active' ? 'selected' : '' }}>Активно</option>
                    <option value="sold" {{ old('status_obyavleniya') == 'sold' ? 'selected' : '' }}>Продано</option>
                    <option value="rented" {{ old('status_obyavleniya') == 'rented' ? 'selected' : '' }}>Сдано</option>
                    <option value="inactive" {{ old('status_obyavleniya') == 'inactive' ? 'selected' : '' }}>Неактивно</option>
                </select>
                @error('status_obyavleniya')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mb-8">
            <label for="images" class="form-label">Фотографии</label>
            <input type="file" id="images" name="images[]" multiple accept="image/*" class="form-input">
            <p class="mt-2 text-sm text-gray-600">Можно выбрать несколько фотографий (максимум 10)</p>
            @error('images')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
            @error('images.*')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="divider pt-6 flex items-center justify-end gap-4">
            <a href="{{ route('admin.properties') }}" class="btn">
                Отмена
            </a>
            <button type="submit" class="btn-primary">
                Создать объявление
            </button>
        </div>
    </form>
</div>
@endsection

