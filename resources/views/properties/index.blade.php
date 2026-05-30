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
                @if(Auth::user()->isClient())
                    <a href="{{ route('properties.selection-request.create') }}" class="btn">Заявка на подбор</a>
                @endif
                <a href="{{ route('properties.create') }}" class="btn-primary">+ Создать объявление</a>
            @else
                <a href="{{ route('login') }}" class="btn">Заявка на подбор</a>
            @endauth
        </div>
    </div>
</div>

{{-- Фильтры каталога — параметры уходят в GET, пагинация сохраняет query --}}
<div class="card p-6 mb-8 catalog-filters">
    <h2 class="text-lg font-bold mb-4">Поиск и фильтры</h2>
    <form id="catalog-filters-form" method="GET" action="{{ route('properties.index') }}">
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
                <select name="type" id="catalog-type-select" class="form-input">
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
                <label class="form-label">Собственников</label>
                <select name="owners_count" class="form-input">
                    <option value="">Любое</option>
                    @foreach(\App\Support\PropertyCatalogFilter::ownersCountOptions() as $value => $label)
                        <option value="{{ $value }}" {{ request('owners_count') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
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
                <input type="number" name="min_price" value="{{ request()->filled('min_price') ? request('min_price') : '' }}" class="form-input" min="0" placeholder="от">
            </div>
            <div>
                <label class="form-label">Цена до (₽)</label>
                <input type="number" name="max_price" value="{{ request()->filled('max_price') ? request('max_price') : '' }}" class="form-input" min="0" placeholder="до">
            </div>
            <div data-show-types="apartment,house">
                <label class="form-label">Комнат от</label>
                <input type="number" name="min_rooms" value="{{ request()->filled('min_rooms') ? request('min_rooms') : '' }}" class="form-input" min="0" max="20" placeholder="от">
            </div>
            <div data-show-types="apartment,house">
                <label class="form-label">Комнат до</label>
                <input type="number" name="max_rooms" value="{{ request()->filled('max_rooms') ? request('max_rooms') : '' }}" class="form-input" min="0" max="20" placeholder="до">
            </div>
            <div data-show-types="apartment,house,commercial">
                <label class="form-label">Площадь от (м²)</label>
                <input type="number" name="min_area" value="{{ request()->filled('min_area') ? request('min_area') : '' }}" class="form-input" min="0" placeholder="от">
            </div>
            <div data-show-types="apartment,house,commercial">
                <label class="form-label">Площадь до (м²)</label>
                <input type="number" name="max_area" value="{{ request()->filled('max_area') ? request('max_area') : '' }}" class="form-input" min="0" placeholder="до">
            </div>
            <div data-show-types="apartment,house">
                <label class="form-label">Этаж от</label>
                <input type="number" name="min_floor" value="{{ request()->filled('min_floor') ? request('min_floor') : '' }}" class="form-input" placeholder="от">
            </div>
            <div data-show-types="apartment,house">
                <label class="form-label">Этаж до</label>
                <input type="number" name="max_floor" value="{{ request()->filled('max_floor') ? request('max_floor') : '' }}" class="form-input" placeholder="до">
            </div>
            <div class="flex items-end min-h-[42px]">
                <label class="flex items-center gap-2.5 cursor-pointer py-2.5">
                    <input type="checkbox" name="has_photos" value="1" class="h-4 w-4 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30" {{ request('has_photos') ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Только с фото</span>
                </label>
            </div>
        </div>

        @include('properties.partials.catalog-house-filters')
        @include('properties.partials.catalog-land-filters')
        @include('properties.partials.catalog-commercial-filters')

        <p class="text-xs text-slate-500 mt-3 hidden" id="catalog-type-hint">Выберите тип недвижимости, чтобы увидеть подходящие фильтры.</p>

        <div class="flex flex-wrap gap-3 mt-4 items-center">
            <button type="submit" class="btn-primary" form="catalog-filters-form">Применить</button>
            <a href="{{ route('properties.index') }}" class="btn">Сбросить</a>
            @auth
                <button type="button" class="btn text-sm" onclick="document.getElementById('save-search-panel').classList.toggle('hidden')">
                    Сохранить поиск
                </button>
            @endauth
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

    @auth
        <div id="save-search-panel" class="hidden mt-4 p-4 border border-brand-200 rounded-xl bg-brand-50/50">
            <form method="POST" action="{{ route('saved-searches.store') }}">
                @csrf
                @foreach(request()->except(['page']) as $key => $val)
                    @if(is_array($val))
                        @foreach($val as $k => $v)
                            <input type="hidden" name="{{ $key }}[{{ $k }}]" value="{{ $v }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endif
                @endforeach
                <label class="form-label">Название сохранённого поиска</label>
                <div class="flex flex-wrap gap-2 mt-1">
                    <input type="text" name="nazvanie" required class="form-input flex-1 min-w-[200px]" placeholder="Например: 2-комн. Иркутск до 5 млн">
                    <label class="flex items-center gap-2 text-sm px-2">
                        <input type="checkbox" name="uvedomleniya" value="1" checked> Уведомлять о новых
                    </label>
                    <button type="submit" class="btn-primary text-sm">Сохранить</button>
                </div>
            </form>
        </div>
    @endauth
</div>

<!-- Карточки объявлений -->
{{-- Сетка карточек — только объявления со статусом «активно» --}}
@if($properties->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($properties as $property)
            @include('properties.partials.property-catalog-card', ['property' => $property])
        @endforeach
    </div>

    <!-- Пагинация -->
    <div class="mt-12">
        {{ $properties->links() }}
    </div>
@else
    @if($hasActiveFilters ?? false)
        @include('properties.partials.catalog-empty-state', [
            'similarProperties' => $similarProperties ?? collect(),
            'capturedFilters' => $capturedFilters ?? [],
        ])
    @else
        <div class="card p-12 text-center">
            <p class="text-xl text-gray-600 mb-4">В каталоге пока нет активных объявлений</p>
            <p class="text-sm text-gray-500">Загляните позже или уточните критерии поиска</p>
        </div>
    @endif
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
