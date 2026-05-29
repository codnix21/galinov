{{-- Каталог объявлений: поиск, фильтры, карточки объектов. --}}
@extends('layouts.app')

@section('title', 'Объявления')

@section('content')
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-4xl font-bold mb-2">Объявления</h1>
            <p class="text-gray-600">Найдено объявлений: {{ $properties->total() }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('properties.map', request()->query()) }}" class="btn">🗺 Карта</a>
            @auth
                <a href="{{ route('properties.create') }}" class="btn-primary">+ Создать объявление</a>
            @endauth
        </div>
    </div>
</div>

{{-- Фильтры каталога — параметры уходят в GET, пагинация сохраняет query --}}
<div class="card p-6 mb-8 catalog-filters">
    <h2 class="text-lg font-bold mb-4">Поиск и фильтры</h2>
    <form method="GET" action="{{ route('properties.index') }}">
        <div class="mb-4">
            <label class="form-label">Поиск</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Квартира Иркутск, адрес или № объявления (#15)">
            <p class="mt-1 text-xs text-gray-500">По словам, адресу, описанию или номеру объявления. Ниже — фильтры по городу, цене и типу.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="form-label">Город</label>
                <select name="city_id" class="form-input">
                    <option value="">Все города</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}" {{ (string) request('city_id') === (string) $city->id ? 'selected' : '' }}>{{ $city->nazvanie }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Тип</label>
                <select name="type" class="form-input">
                    <option value="">Все</option>
                    @foreach(\App\Models\Property::tipNazvaniya() as $value => $label)
                        <option value="{{ $value }}" {{ request('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Операция</label>
                <select name="operation" class="form-input">
                    <option value="">Все</option>
                    <option value="sale" {{ request('operation') == 'sale' ? 'selected' : '' }}>Продажа</option>
                    <option value="rent" {{ request('operation') == 'rent' ? 'selected' : '' }}>Аренда</option>
                </select>
            </div>
            <div>
                <label class="form-label">Сортировка</label>
                <select name="sort" class="form-input">
                    @foreach(\App\Support\PropertyCatalogFilter::sortOptions() as $value => $label)
                        <option value="{{ $value }}" {{ request('sort', 'newest') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Цена от (₽)</label>
                <input type="number" name="min_price" value="{{ request('min_price') }}" class="form-input" min="0" placeholder="0">
            </div>
            <div>
                <label class="form-label">Цена до (₽)</label>
                <input type="number" name="max_price" value="{{ request('max_price') }}" class="form-input" min="0" placeholder="10000000">
            </div>
            <div>
                <label class="form-label">Комнат от</label>
                <input type="number" name="min_rooms" value="{{ request('min_rooms') }}" class="form-input" min="0" max="20" placeholder="0">
            </div>
            <div>
                <label class="form-label">Комнат до</label>
                <input type="number" name="max_rooms" value="{{ request('max_rooms') }}" class="form-input" min="0" max="20">
            </div>
            <div>
                <label class="form-label">Площадь от (м²)</label>
                <input type="number" name="min_area" value="{{ request('min_area') }}" class="form-input" min="0">
            </div>
            <div>
                <label class="form-label">Площадь до (м²)</label>
                <input type="number" name="max_area" value="{{ request('max_area') }}" class="form-input" min="0">
            </div>
            <div>
                <label class="form-label">Этаж от</label>
                <input type="number" name="min_floor" value="{{ request('min_floor') }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Этаж до</label>
                <input type="number" name="max_floor" value="{{ request('max_floor') }}" class="form-input">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="has_photos" value="1" class="rounded border-slate-300" {{ request('has_photos') ? 'checked' : '' }}>
                    <span class="text-sm">Только с фото</span>
                </label>
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button type="submit" class="btn-primary">Применить</button>
            <a href="{{ route('properties.index') }}" class="btn">Сбросить</a>
        </div>
        @php $activeKeys = \App\Support\PropertyCatalogFilter::activeFilterKeys(request()); @endphp
        @if(count($activeKeys) > 0)
            <div class="mt-4 text-sm text-gray-600 flex flex-wrap gap-2 items-center">
                <span>Активные фильтры:</span>
                @if(request('search')) <span class="badge">Поиск: {{ request('search') }}</span> @endif
                @if(request('city_id'))
                    <span class="badge">Город: {{ $cities->firstWhere('id', (int) request('city_id'))?->nazvanie ?? request('city_id') }}</span>
                @endif
                @if(request('type')) <span class="badge">Тип: {{ \App\Models\Property::nazvanieTipa(request('type')) }}</span> @endif
                @if(request('operation')) <span class="badge">{{ request('operation') === 'rent' ? 'Аренда' : 'Продажа' }}</span> @endif
                @if(request('min_price')) <span class="badge">Цена от {{ number_format(request('min_price'), 0, ',', ' ') }} ₽</span> @endif
                @if(request('max_price')) <span class="badge">Цена до {{ number_format(request('max_price'), 0, ',', ' ') }} ₽</span> @endif
                @if(request('min_rooms')) <span class="badge">Комнат от {{ request('min_rooms') }}</span> @endif
                @if(request('max_rooms')) <span class="badge">Комнат до {{ request('max_rooms') }}</span> @endif
                @if(request('min_area')) <span class="badge">Площадь от {{ request('min_area') }} м²</span> @endif
                @if(request('max_area')) <span class="badge">Площадь до {{ request('max_area') }} м²</span> @endif
                @if(request('has_photos')) <span class="badge">С фото</span> @endif
                @if(request('sort') && request('sort') !== 'newest')
                    <span class="badge">{{ \App\Support\PropertyCatalogFilter::sortOptions()[request('sort')] ?? request('sort') }}</span>
                @endif
            </div>
        @endif
    </form>
</div>

<!-- Карточки объявлений -->
{{-- Сетка карточек — только объявления со статусом «активно» --}}
@if($properties->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($properties as $property)
            <div class="card p-6 group relative">
                <!-- Кнопка избранного -->
                @auth
                    <div class="absolute top-3 right-3 z-20" onclick="event.stopPropagation()">
                        @if(isset($property->is_favorite) && $property->is_favorite)
                            <form action="{{ route('favorites.destroy', $property) }}" method="POST" class="favorite-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="favorite-btn favorite-btn--active" title="Убрать из избранного" aria-label="Убрать из избранного">★</button>
                            </form>
                        @else
                            <form action="{{ route('favorites.store', $property) }}" method="POST" class="favorite-form">
                                @csrf
                                <button type="submit" class="favorite-btn favorite-btn--inactive" title="В избранное" aria-label="Добавить в избранное">☆</button>
                            </form>
                        @endif
                    </div>
                @endauth
                
                <div class="cursor-pointer" onclick="window.location='{{ route('properties.show', $property) }}'">
                    <!-- Блок для фотографий -->
                    <div class="mb-4 h-48 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                        @if($property->images && $property->images->count() > 0)
                            <img src="{{ $property->images->first()->public_url }}" alt="{{ $property->nazvanie }}" class="w-full h-full object-cover">
                        @else
                            <div class="text-gray-400 text-sm">Нет фотографии</div>
                        @endif
                    </div>
                
                <div class="flex items-center justify-between mb-3">
                    <div class="flex gap-2 flex-wrap">
                        <span class="badge">{{ $property->type_name }}</span>
                        <span class="badge">{{ $property->operation_name }}</span>
                    </div>
                </div>
                <h3 class="text-xl font-bold mb-3 group-hover:underline">
                    {{ $property->nazvanie }}
                </h3>
                <p class="text-gray-600 text-sm mb-4 line-clamp-2 min-h-[2.5rem]">
                    {{ $property->opisanie }}
                </p>
                <div class="space-y-2 mb-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Город:</span>
                        <span class="font-medium">{{ $property->gorod ?? 'Не указан' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Адрес:</span>
                        <span class="font-medium">{{ Str::limit($property->adres_ulitsy ?? '', 25) }}</span>
                    </div>
                    @if($property->user)
                        <div class="flex items-center gap-2 pt-2 border-t border-gray-200">
                            @if($property->user->avatar_polzovatela)
                                <img src="{{ $property->user->avatar_url }}" alt="{{ trim($property->user->familia . ' ' . $property->user->imya . ' ' . $property->user->otchestvo) }}" class="w-8 h-8 rounded-full object-cover">
                            @else
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold text-xs">
                                    {{ mb_substr($property->user->imya ?? '', 0, 1) }}
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-xs truncate">{{ trim($property->user->familia . ' ' . $property->user->imya . ' ' . $property->user->otchestvo) }}</div>
                                @if($property->user->telefon)
                                    <div class="text-xs text-gray-600">{{ $property->user->telefon }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                    @if($property->ploshchad)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Площадь:</span>
                            <span class="font-medium">{{ $property->ploshchad }} м²</span>
                        </div>
                    @endif
                    @if($property->komnaty)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Комнат:</span>
                            <span class="font-medium">{{ $property->komnaty }}</span>
                        </div>
                    @endif
                    @if($property->etazh)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Этаж:</span>
                            <span class="font-medium">{{ $property->etazh }}{{ $property->vsego_etazhey ? '/' . $property->vsego_etazhey : '' }}</span>
                        </div>
                    @endif
                </div>
                <div class="divider pt-4 mt-4">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl font-bold">{{ number_format($property->tsena, 0, ',', ' ') }} ₽</span>
                        <span class="text-xs text-gray-500">→</span>
                    </div>
                </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Пагинация -->
    <div class="mt-12">
        {{ $properties->links() }}
    </div>
@else
    <div class="card p-12 text-center">
        <p class="text-xl text-gray-600 mb-4">Объявления не найдены</p>
        <p class="text-sm text-gray-500">Попробуйте изменить параметры фильтрации</p>
    </div>
@endif

<script>
// Обработка форм избранного без перезагрузки страницы
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.favorite-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const formData = new FormData(this);
            const url = this.action;
            const method = formData.get('_method') || 'POST';
            const button = this.querySelector('button');

            fetch(url, {
                method: method === 'DELETE' ? 'DELETE' : 'POST',
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Обновляем иконку звездочки
                    if (method === 'DELETE') {
                        // Меняем на пустую звездочку
                        button.innerHTML = '☆';
                        button.className = 'text-gray-400 hover:text-yellow-500 text-2xl';
                        // Меняем форму на добавление
                        const methodInput = this.querySelector('input[name="_method"]');
                        if (methodInput) {
                            methodInput.remove();
                        }
                    } else {
                        // Меняем на заполненную звездочку
                        button.innerHTML = '★';
                        button.className = 'text-yellow-500 hover:text-yellow-600 text-2xl';
                        // Меняем форму на удаление
                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'DELETE';
                        this.appendChild(methodInput);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.location.reload();
            });
        });
    });
});
</script>
@endsection
