{{-- Админ редактирует объявление. --}}
@extends('layouts.app')

@section('title', 'Редактировать объявление')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Редактировать объявление</h1>
        <p class="text-gray-600">Внесите изменения в объявление</p>
    </div>

    <form method="POST" action="{{ route('admin.properties.update', $property) }}" class="card p-8" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <label for="title" class="form-label">Название *</label>
            <input type="text" id="nazvanie" name="nazvanie" value="{{ old('nazvanie', $property->nazvanie ?? $property->title) }}" required class="form-input">
            @error('nazvanie')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="description" class="form-label">Описание *</label>
            <textarea id="opisanie" name="opisanie" rows="6" required class="form-input">{{ old('opisanie', $property->opisanie ?? $property->description) }}</textarea>
            @error('opisanie')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="type" class="form-label">Тип *</label>
                <select id="tip" name="tip" required class="form-input select-native">
                    @foreach(\App\Models\Property::tipNazvaniya() as $value => $label)
                        <option value="{{ $value }}" {{ old('tip', $property->tip ?? $property->type) == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('tip')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="operation" class="form-label">Операция *</label>
                <select id="operatsiya" name="operatsiya" required class="form-input select-native">
                    <option value="sale" {{ old('operatsiya', $property->operatsiya ?? $property->operation) == 'sale' ? 'selected' : '' }}>Продажа</option>
                    <option value="rent" {{ old('operatsiya', $property->operatsiya ?? $property->operation) == 'rent' ? 'selected' : '' }}>Аренда</option>
                </select>
                @error('operatsiya')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mb-6">
            <label for="price" class="form-label">Цена (₽) *</label>
            <input type="number" id="tsena" name="tsena" value="{{ old('tsena', $property->tsena ?? $property->price) }}" step="0.01" min="0" required class="form-input">
            @error('tsena')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="city" class="form-label">Город *</label>
                <input type="text" id="gorod" name="gorod" value="{{ old('gorod', $property->gorod ?? $property->city) }}" required class="form-input dadata-city" data-url="{{ route('api.dadata.city') }}" placeholder="Например: Иркутск" autocomplete="off">
                @error('gorod')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Подсказки только по населённым пунктам.</p>
            </div>
            <div>
                <label for="street_address" class="form-label">Улица и дом *</label>
                <input type="text" id="adres_ulitsy" name="adres_ulitsy" value="{{ old('adres_ulitsy', $property->adres_ulitsy ?? $property->street_address) }}" required class="form-input dadata-address" data-url="{{ route('api.dadata.address') }}" placeholder="ул. Ленина, д. 1" autocomplete="off">
                @error('adres_ulitsy')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Улица и дом без города; подсказка задаёт точку на карте.</p>
            </div>
        </div>
        <input type="hidden" name="geo_shirota" value="{{ old('geo_shirota', $property->geo_shirota) }}">
        <input type="hidden" name="geo_dolgota" value="{{ old('geo_dolgota', $property->geo_dolgota) }}">

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="area" class="form-label">Площадь (м²)</label>
                <input type="number" id="ploshchad" name="ploshchad" value="{{ old('ploshchad', $property->ploshchad ?? $property->area) }}" min="0" class="form-input">
                @error('ploshchad')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="rooms" class="form-label">Количество комнат</label>
                <input type="number" id="komnaty" name="komnaty" value="{{ old('komnaty', $property->komnaty ?? $property->rooms) }}" min="0" class="form-input">
                @error('komnaty')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="floor" class="form-label">Этаж</label>
                <input type="number" id="etazh" name="etazh" value="{{ old('etazh', $property->etazh ?? $property->floor) }}" class="form-input">
                @error('etazh')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="total_floors" class="form-label">Всего этажей</label>
                <input type="number" id="vsego_etazhey" name="vsego_etazhey" value="{{ old('vsego_etazhey', $property->vsego_etazhey ?? $property->total_floors) }}" min="0" class="form-input">
                @error('vsego_etazhey')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        @include('properties.partials.house-fields', ['property' => $property])
        @include('properties.partials.land-fields', ['property' => $property])
        @include('properties.partials.commercial-fields', ['property' => $property])

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label for="user_id" class="form-label">Риэлтор *</label>
                <select id="polzovatel_id" name="polzovatel_id" required class="form-input">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('polzovatel_id', $property->polzovatel_id ?? $property->user_id) == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('polzovatel_id')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="form-label">Статус *</label>
                <select id="status_obyavleniya" name="status_obyavleniya" required class="form-input select-native">
                    <option value="draft" {{ old('status_obyavleniya', $property->status_obyavleniya ?? $property->status) == 'draft' ? 'selected' : '' }}>Черновик</option>
                    <option value="pending_review" {{ old('status_obyavleniya', $property->status_obyavleniya ?? $property->status) == 'pending_review' ? 'selected' : '' }}>На модерации</option>
                    <option value="active" {{ old('status_obyavleniya', $property->status_obyavleniya ?? $property->status) == 'active' ? 'selected' : '' }}>Активно</option>
                    <option value="sold" {{ old('status_obyavleniya', $property->status_obyavleniya ?? $property->status) == 'sold' ? 'selected' : '' }}>Продано</option>
                    <option value="rented" {{ old('status_obyavleniya', $property->status_obyavleniya ?? $property->status) == 'rented' ? 'selected' : '' }}>Сдано</option>
                    <option value="inactive" {{ old('status_obyavleniya', $property->status_obyavleniya ?? $property->status) == 'inactive' ? 'selected' : '' }}>Неактивно</option>
                </select>
                @error('status_obyavleniya')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mb-6">
            <label class="form-label">Текущие фотографии</label>
            @if($property->images && $property->images->count() > 0)
                <div class="grid grid-cols-4 gap-4 mt-2 mb-4">
                    @foreach($property->images as $image)
                        <div class="relative">
                            <img src="{{ $image->public_url }}" alt="Фото" class="w-full h-24 object-cover rounded border">
                            <label class="absolute top-1 left-1 flex items-center gap-1">
                                <input type="checkbox" name="delete_images[]" value="{{ $image->id }}" class="rounded">
                                <span class="text-xs text-white bg-black bg-opacity-50 px-1 rounded">Удалить</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-600 mt-2">Фотографии не добавлены</p>
            @endif
        </div>

        <div class="mb-8">
            <label for="images" class="form-label">Добавить фотографии</label>
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
                Сохранить изменения
            </button>
        </div>
    </form>

    @include('properties.partials.property-owners-form', ['property' => $property])

    <div class="mt-10 w-full min-w-0">
        @include('partials.property-zhurnal')
    </div>
</div>
@endsection

