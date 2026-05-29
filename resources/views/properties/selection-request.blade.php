{{-- Заявка клиента на подбор недвижимости (услуга агентства) --}}
@extends('layouts.app')

@section('title', 'Заявка на подбор')

@section('content')
<div class="max-w-4xl mx-auto mb-8">
    <h1 class="text-3xl font-bold mb-2">Заявка на подбор недвижимости</h1>
    <p class="text-gray-600">Опишите критерии — риэлтор агентства подберёт объекты и свяжется с вами. Это отдельная услуга, не привязанная к одному объявлению.</p>
</div>

<div class="card p-8 max-w-4xl mx-auto">
    <form method="POST" action="{{ route('properties.selection-request.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="istochnik" value="form">

        <div>
            <h2 class="text-lg font-bold mb-4">Критерии подбора</h2>
            @php
                $f = fn ($key, $default = '') => old("filters.{$key}", $oldFilters[$key] ?? $default);
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Тип</label>
                    <select name="filters[type]" class="form-input select-native" id="tip">
                        <option value="">Любой</option>
                        @foreach(\App\Models\Property::tipNazvaniya() as $value => $label)
                            <option value="{{ $value }}" {{ $f('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Операция</label>
                    <select name="filters[operation]" class="form-input">
                        <option value="">Любая</option>
                        <option value="sale" {{ $f('operation') === 'sale' ? 'selected' : '' }}>Покупка</option>
                        <option value="rent" {{ $f('operation') === 'rent' ? 'selected' : '' }}>Аренда</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Город</label>
                    <select name="filters[city_id]" class="form-input">
                        <option value="">Любой</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" {{ (string) $f('city_id') === (string) $city->id ? 'selected' : '' }}>{{ $city->nazvanie }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Цена от (₽)</label>
                    <input type="number" name="filters[min_price]" value="{{ $f('min_price') }}" min="0" class="form-input">
                </div>
                <div>
                    <label class="form-label">Цена до (₽)</label>
                    <input type="number" name="filters[max_price]" value="{{ $f('max_price') }}" min="0" class="form-input">
                </div>
                <div>
                    <label class="form-label">Комнат от</label>
                    <input type="number" name="filters[min_rooms]" value="{{ $f('min_rooms') }}" min="0" class="form-input">
                </div>
                <div>
                    <label class="form-label">Комнат до</label>
                    <input type="number" name="filters[max_rooms]" value="{{ $f('max_rooms') }}" min="0" class="form-input">
                </div>
                <div>
                    <label class="form-label">Площадь от (м²)</label>
                    <input type="number" name="filters[min_area]" value="{{ $f('min_area') }}" min="0" class="form-input">
                </div>
                <div>
                    <label class="form-label">Площадь до (м²)</label>
                    <input type="number" name="filters[max_area]" value="{{ $f('max_area') }}" min="0" class="form-input">
                </div>
            </div>
            @include('properties.partials.catalog-house-filters', ['filterPrefix' => 'filters'])
        </div>

        <div class="border-t border-slate-200 pt-6">
            <h2 class="text-lg font-bold mb-4">Контакты</h2>
            @php $user = auth()->user(); @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="form-label">Имя *</label>
                    <input type="text" name="imya" required class="form-input" value="{{ old('imya', $user ? trim($user->imya.' '.$user->familia) : '') }}">
                </div>
                <div>
                    <label class="form-label">Телефон</label>
                    <input type="text" name="telefon" class="form-input" value="{{ old('telefon', $user->telefon ?? '') }}">
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="{{ old('email', $user->email_polzovatela ?? '') }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Пожелания</label>
                    <textarea name="kommentariy" rows="4" class="form-input" placeholder="Район, сроки, особенности…">{{ old('kommentariy') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Отправить заявку на подбор</button>
            <a href="{{ route('properties.index') }}" class="btn">Смотреть каталог</a>
        </div>
    </form>
</div>
@endsection
