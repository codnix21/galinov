{{-- Страница одного объявления с фото и описанием. --}}
@extends('layouts.app')

@section('title', $property->nazvanie)

@section('content')
@php
    $canManage = (bool) ($canManage ?? (Auth::check() && \App\Support\PropertyListingAuthor::canManage(Auth::user(), $property)));
    $docsReady = (bool) ($docsReady ?? false);
    $profileVerifiedTips = $profileVerifiedTips ?? [];
    $canPublishToModeration = (bool) ($canPublishToModeration ?? false);
@endphp
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('properties.index') }}" class="text-sm hover:underline flex items-center gap-1">
        <span>←</span> <span>Назад к списку</span>
    </a>
    {{-- Избранное — только для вошедших пользователей --}}
    @auth
        <div>
            @if(isset($property->is_favorite) && $property->is_favorite)
                <form action="{{ route('favorites.destroy', $property) }}" method="POST" class="inline favorite-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="favorite-btn favorite-btn--active" title="Убрать из избранного" aria-label="Убрать из избранного">★</button>
                </form>
            @else
                <form action="{{ route('favorites.store', $property) }}" method="POST" class="inline favorite-form">
                    @csrf
                    <button type="submit" class="favorite-btn favorite-btn--inactive" title="В избранное" aria-label="Добавить в избранное">☆</button>
                </form>
            @endif
        </div>
    @endauth
</div>

@php
    $st = $property->status_obyavleniya ?? $property->status;
    $listingUnavailable = $listingUnavailable ?? null;
@endphp
@if($listingUnavailable === 'sold')
    <div class="mb-6 p-4 rounded-xl border-2 border-slate-300 bg-slate-100 text-slate-800 text-sm">
        <p class="font-semibold mb-1">Объект продан</p>
        <p class="text-slate-600">Объявление снято с витрины. Просмотр доступен по ссылке, новые заявки и покупка недоступны.</p>
    </div>
@elseif($listingUnavailable === 'rented')
    <div class="mb-6 p-4 rounded-xl border-2 border-slate-300 bg-slate-100 text-slate-800 text-sm">
        <p class="font-semibold mb-1">Объект сдан в аренду</p>
        <p class="text-slate-600">Объявление снято с витрины. Просмотр доступен по ссылке, новые заявки и онлайн-покупка недоступны.</p>
    </div>
@endif
@if($st === 'pending_review')
    <div class="mb-6 p-4 border-2 border-amber-400 bg-amber-50 text-amber-950 text-sm">
        Объявление на модерации. После проверки сотрудником оно появится в общем каталоге.
    </div>
@endif
@auth
    @if($property->sozdal_kak && $property->sozdal_kak !== 'client' && (Auth::user()->isStaff() || \App\Support\PropertyListingAuthor::canManage(Auth::user(), $property)))
        <div class="mb-6 p-4 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800">
            <strong>Размещение:</strong> {{ \App\Support\PropertyListingAuthor::label($property->sozdal_kak) }}
            @if($property->realtor && (int) $property->realtor->id !== (int) $property->polzovatel_id)
                · риэлтор {{ trim($property->realtor->familia.' '.$property->realtor->imya) }}
            @endif
            <span class="block text-xs text-gray-600 mt-1">{{ \App\Support\PropertyListingAuthor::description($property->sozdal_kak) }}</span>
        </div>
    @endif
@endauth
@if($st === 'draft' && !empty($property->prichina_otkaza_mod))
    <div class="mb-6 p-4 border-2 border-red-300 bg-red-50 text-red-900 text-sm">
        <p class="font-semibold mb-1">Публикация отклонена модератором</p>
        <p class="whitespace-pre-line">{{ $property->prichina_otkaza_mod }}</p>
        @if($canManage)
            <p class="mt-3 text-gray-800">Что сделать:</p>
            <ol class="mt-2 list-decimal list-inside space-y-1 text-gray-800">
                <li><a href="{{ route('profile.documents.index') }}" class="underline font-medium">Профиль → документы и персональные данные</a></li>
                <li><a href="{{ route('properties.documents', $property) }}" class="underline font-medium">Документы на этот объект</a></li>
                <li><a href="{{ route('properties.edit', $property) }}" class="underline font-medium">Редактировать объявление</a></li>
                <li>Когда всё готово — «Отправить на модерацию» в блоке статуса справа</li>
            </ol>
        @else
            <p class="mt-2 text-gray-700">Внесите правки и снова отправьте объявление на модерацию.</p>
        @endif
    </div>
@endif

{{-- Галерея слева, характеристики и действия справа --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        {{-- Фотографии объявления --}}
        <div class="card p-8 mb-6">
            <h2 class="text-2xl font-bold mb-4">Фотографии</h2>
            @if($property->images && $property->images->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($property->images as $index => $image)
                        <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden cursor-pointer hover:opacity-90 transition-opacity" onclick="openImageModal({{ $index }})">
                            <img src="{{ $image->public_url }}" alt="{{ $property->nazvanie }}" class="w-full h-full object-cover">
                        </div>
                    @endforeach
                </div>
            @else
                <div class="h-64 bg-gray-100 rounded-lg flex items-center justify-center">
                    <p class="text-gray-400">Фотографии не добавлены</p>
                </div>
            @endif
        </div>

        <!-- Модальное окно для просмотра фотографий -->
        @if($property->images && $property->images->count() > 0)
        <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center">
            <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-4xl hover:text-gray-300 z-10">&times;</button>
            <button onclick="previousImage()" class="absolute left-4 text-white text-4xl hover:text-gray-300 z-10">‹</button>
            <button onclick="nextImage()" class="absolute right-16 text-white text-4xl hover:text-gray-300 z-10">›</button>
            <div class="max-w-7xl max-h-full p-4">
                <img id="modalImage" src="" alt="{{ $property->nazvanie }}" class="max-w-full max-h-[90vh] object-contain mx-auto">
                <div class="text-center text-white mt-4">
                    <span id="imageCounter"></span>
                </div>
            </div>
        </div>

        <script>
            let currentImageIndex = 0;
            const images = [
                @foreach($property->images as $image)
                    '{{ $image->public_url }}',
                @endforeach
            ];

            function openImageModal(index) {
                currentImageIndex = index;
                document.getElementById('imageModal').classList.remove('hidden');
                updateModalImage();
            }

            function closeImageModal() {
                document.getElementById('imageModal').classList.add('hidden');
            }

            function updateModalImage() {
                document.getElementById('modalImage').src = images[currentImageIndex];
                document.getElementById('imageCounter').textContent = (currentImageIndex + 1) + ' / ' + images.length;
            }

            function nextImage() {
                currentImageIndex = (currentImageIndex + 1) % images.length;
                updateModalImage();
            }

            function previousImage() {
                currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
                updateModalImage();
            }

            // Закрытие по клику вне изображения
            document.getElementById('imageModal').addEventListener('click', function(e) {
                if (e.target.id === 'imageModal') {
                    closeImageModal();
                }
            });

            // Навигация клавиатурой
            document.addEventListener('keydown', function(e) {
                const modal = document.getElementById('imageModal');
                if (!modal.classList.contains('hidden')) {
                    if (e.key === 'Escape') closeImageModal();
                    if (e.key === 'ArrowRight') nextImage();
                    if (e.key === 'ArrowLeft') previousImage();
                }
            });
        </script>
        @endif

        <div class="card p-8 mb-6">
            <div class="flex items-center gap-2 mb-6 flex-wrap">
                <span class="badge">{{ $property->type_name }}</span>
                <span class="badge">{{ $property->operation_name }}</span>
                <span class="badge">{{ $property->status_name }}</span>
            </div>
            <h1 class="text-4xl font-bold mb-4">{{ $property->nazvanie }}</h1>
            <div class="text-4xl font-bold mb-8 pb-6 divider">
                {{ number_format((float)($property->tsena ?? 0), 0, ',', ' ') }} ₽
            </div>
            
            @include('properties.partials.property-owners-display')

            <div class="mb-8">
                <h2 class="text-2xl font-bold mb-4">Описание</h2>
                <div class="prose prose-sm max-w-none">
                    <p class="text-gray-700 whitespace-pre-line leading-relaxed">{{ $property->opisanie }}</p>
                </div>
            </div>

            <div class="divider pt-6">
                <h2 class="text-2xl font-bold mb-6">Характеристики</h2>
                <div class="grid grid-cols-2 gap-6">
                    <div class="pb-4 border-b border-gray-200">
                        <span class="text-sm text-gray-600 block mb-1">Город</span>
                        <span class="font-medium text-lg">{{ $property->gorod ?? 'Не указан' }}</span>
                    </div>
                    <div class="pb-4 border-b border-gray-200">
                        <span class="text-sm text-gray-600 block mb-1">Улица и дом</span>
                        <span class="font-medium text-lg">{{ $property->adres_ulitsy ?? 'Не указан' }}</span>
                    </div>
                    @if($property->ploshchad)
                        <div class="pb-4 border-b border-gray-200">
                            <span class="text-sm text-gray-600 block mb-1">Площадь</span>
                            <span class="font-medium text-lg">{{ $property->ploshchad }} м²</span>
                        </div>
                    @endif
                    @if(($property->tip ?? '') !== 'land')
                        <div class="pb-4 border-b border-gray-200">
                            <span class="text-sm text-gray-600 block mb-1">{{ ($property->tip ?? '') === 'commercial' ? 'Планировка' : 'Комнат' }}</span>
                            <span class="font-medium text-lg">
                                @if($property->komnaty)
                                    {{ $property->komnaty }}
                                @elseif(($property->tip ?? '') === 'commercial')
                                    открытая планировка
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                        <div class="pb-4 border-b border-gray-200">
                            <span class="text-sm text-gray-600 block mb-1">Этаж</span>
                            <span class="font-medium text-lg">
                                @if($property->etazh)
                                    {{ $property->etazh }}{{ $property->vsego_etazhey ? '/' . $property->vsego_etazhey : '' }}
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                    @endif
                    @include('properties.partials.house-characteristics')
                    @include('properties.partials.land-characteristics')
                    @include('properties.partials.commercial-characteristics')
                    @if($property->user)
                    <div class="pb-4 border-b border-gray-200">
                        <span class="text-sm text-gray-600 block mb-1">Автор объявления</span>
                        <span class="font-medium text-lg">{{ trim($property->user->familia . ' ' . $property->user->imya . ' ' . $property->user->otchestvo) }}</span>
                    </div>
                    @endif
                    <div class="pb-4 border-b border-gray-200">
                        <span class="text-sm text-gray-600 block mb-1">Дата публикации</span>
                        <span class="font-medium text-lg">{{ $property->sozdano_at ? $property->sozdano_at->format('d.m.Y') : ($property->created_at ? $property->created_at->format('d.m.Y') : 'Не указана') }}</span>
                    </div>
                </div>
            </div>
        </div>

        @include('partials.property-zhurnal')

        <div class="card p-8 mb-6">
            <h2 class="text-2xl font-bold mb-4">На карте</h2>
            @if($mapLat !== null && $mapLon !== null)
                @php
                    $ymLon = number_format((float) $mapLon, 6, '.', '');
                    $ymLat = number_format((float) $mapLat, 6, '.', '');
                    $ymCenter = $ymLon . ',' . $ymLat;
                    $ymPoint = $ymCenter . ',pm2rdm'; // красный маркер
                    $ymUrl = 'https://yandex.ru/map-widget/v1/?ll=' . rawurlencode($ymCenter)
                        . '&z=17&l=map&pt=' . rawurlencode($ymPoint);
                @endphp
                <div class="rounded-lg overflow-hidden border border-gray-200">
                    <iframe
                        src="{{ $ymUrl }}"
                        width="100%"
                        height="320"
                        frameborder="0"
                        allowfullscreen="true"
                        style="border:0;"
                    ></iframe>
                </div>
                <p class="mt-3 text-sm text-gray-600">Метка соответствует адресу: {{ $property->gorod }}, {{ $property->adres_ulitsy }}.</p>
            @elseif(!empty($mapAddressQuery))
                @php
                    $ymUrl = 'https://yandex.ru/map-widget/v1/?mode=search&z=17&l=map&text=' . rawurlencode($mapAddressQuery);
                @endphp
                <div class="rounded-lg overflow-hidden border border-gray-200">
                    <iframe
                        src="{{ $ymUrl }}"
                        width="100%"
                        height="320"
                        frameborder="0"
                        allowfullscreen="true"
                        style="border:0;"
                    ></iframe>
                </div>
                <p class="mt-3 text-sm text-gray-600">Карта открыта по адресу: {{ $property->gorod }}, {{ $property->adres_ulitsy }}.</p>
            @else
                <p class="text-sm text-gray-600">Не удалось показать точку на карте. Проверьте, что в объявлении заполнены город и улица с номером дома, затем сохраните изменения.</p>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        @auth
            @if($canManage || Auth::user()->isAdmin())
                @include('properties.partials.listing-stepper', [
                    'property' => $property,
                    'docsReady' => $docsReady,
                    'st' => $st,
                    'profileVerifiedTips' => $profileVerifiedTips,
                    'canPublishToModeration' => $canPublishToModeration ?? false,
                    'canManage' => $canManage,
                ])
            @endif
        @endauth
        @php
            $panoramaUrl = \App\Services\PropertyReportService::panoramaUrl($property);
            $isActiveListing = ($property->status_obyavleniya ?? $property->status ?? '') === 'active';
            $canInquire = $isActiveListing && (!Auth::check() || (int)Auth::id() !== (int)($property->polzovatel_id ?? 0));
        @endphp
        @if(empty($listingUnavailable))
        @include('properties.partials.property-info-requests', [
            'property' => $property,
            'canAsk' => $canAskInfo ?? false,
            'infoRequests' => $infoRequests ?? collect(),
        ])
        @endif

        @if($canInquire && empty($listingUnavailable))
        <div class="card p-6">
            <h3 class="text-xl font-bold mb-3">Заявка на объект</h3>
            <p class="text-sm text-gray-600 mb-4">Оставьте контакты — риэлтор перезвонит в ближайшее время.</p>
            <form method="POST" action="{{ route('properties.inquiry', $property) }}" class="space-y-3">
                @csrf
                <input type="text" name="imya" required class="form-input" placeholder="Ваше имя" value="{{ Auth::user()?->name }}">
                <input type="tel" name="telefon" class="form-input" placeholder="Телефон">
                <input type="email" name="email" class="form-input" placeholder="Email" value="{{ Auth::user()?->email_polzovatela }}">
                <textarea name="kommentariy" rows="2" class="form-input" placeholder="Комментарий"></textarea>
                <button type="submit" class="btn-primary w-full">Отправить заявку</button>
            </form>
        </div>
        @endif
        <div class="card p-6 border-2 border-brand-200 bg-brand-50/40">
            <h3 class="text-xl font-bold mb-3">Онлайн-сделка</h3>
            <div class="space-y-2">
                @if($panoramaUrl)
                    <a href="{{ $panoramaUrl }}" target="_blank" rel="noopener" class="btn block text-center text-sm w-full">Панорама района</a>
                @endif
                <a href="{{ route('pages.mortgage-calculator', ['price' => (int)$property->tsena, 'property_id' => $property->id]) }}" class="btn block text-center text-sm w-full">Стоимость кредита</a>
                <a href="{{ route('properties.report', $property) }}" class="btn block text-center text-sm w-full">
                    Полный отчёт по объекту
                </a>
                @auth
                    @if($isActiveListing && (int)Auth::id() !== (int)($property->polzovatel_id ?? 0))
                        <a href="{{ route('purchase.buy', $property) }}" class="btn-primary block text-center text-sm w-full">Купить без риэлтора</a>
                    @endif
                    @if(($canManage ?? false) && ($isActiveListing ?? false))
                        <a href="{{ route('deals.express', $property) }}" class="btn block text-center text-sm w-full border-green-600 text-green-800">Экспресс-сделка (договор авто)</a>
                    @endif
                @else
                    @if($isActiveListing)
                        <a href="{{ route('login') }}" class="btn-primary block text-center text-sm w-full">Войти и купить онлайн</a>
                    @endif
                @endauth
            </div>
        </div>

        @if($property->user)
        <div class="card p-6">
            <h3 class="text-xl font-bold mb-4">Контактная информация</h3>
            <div class="space-y-4">
                <div class="flex items-center gap-4 pb-4 border-b border-gray-200">
                    @if($property->user->avatar_url)
                        <img src="{{ $property->user->avatar_url }}" alt="{{ trim($property->user->familia . ' ' . $property->user->imya . ' ' . $property->user->otchestvo) }}" class="w-16 h-16 rounded-full object-cover">
                    @else
                        <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold text-xl">
                            {{ mb_substr($property->user->imya ?? '', 0, 1) }}
                        </div>
                    @endif
                    <div>
                        <div class="font-medium text-lg">{{ trim($property->user->familia . ' ' . $property->user->imya . ' ' . $property->user->otchestvo) }}</div>
                        @if($property->user->biografiya)
                            <p class="text-sm text-gray-600 mt-1">{{ Str::limit($property->user->biografiya, 100) }}</p>
                        @endif
                    </div>
                </div>
                @if($property->user->telefon)
                    <div>
                        <span class="text-sm text-gray-600 block">Телефон</span>
                        <a href="tel:{{ $property->user->telefon }}" class="font-medium hover:underline">
                            {{ $property->user->telefon }}
                        </a>
                    </div>
                @endif
                <div>
                    <span class="text-sm text-gray-600 block">Email</span>
                    <a href="mailto:{{ $property->user->email_polzovatela }}" class="font-medium hover:underline">
                        {{ $property->user->email_polzovatela }}
                    </a>
                </div>
                @if($property->user->biografiya)
                    <div class="pt-2">
                        <span class="text-sm text-gray-600 block mb-2">О себе</span>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $property->user->biografiya }}</p>
                    </div>
                @endif
            </div>
        </div>
        @endif

        @auth
            @if($canManage || Auth::user()->isAdmin())
                <div class="card p-6">
                    <h3 class="text-xl font-bold mb-4">Управление</h3>
                    @if(in_array('passport', $profileVerifiedTips, true))
                        <p class="mb-3">
                            <span class="badge bg-green-100 text-green-800">Готов к размещению — паспорт проверен</span>
                        </p>
                    @endif
                    <div class="text-sm text-slate-600 mb-4 space-y-1">
                        <p>
                            <strong>Паспорт:</strong>
                            @if(in_array('passport', $profileVerifiedTips, true))
                                <span class="text-green-700 font-medium">проверен в профиле</span>
                            @else
                                <span class="text-amber-700 font-medium">не проверен</span>
                                <a href="{{ route('profile.documents.index') }}" class="underline ml-1">загрузить</a>
                            @endif
                        </p>
                        <p>
                            <strong>ИНН/СНИЛС:</strong>
                            @if(in_array('inn', $profileVerifiedTips, true))
                                <span class="text-green-700 font-medium">проверен</span>
                            @else
                                <span class="text-slate-500">необязательно</span>
                            @endif
                        </p>
                    </div>
                    <div class="space-y-3">
                        <a href="{{ route('properties.documents', $property) }}" class="btn block text-center">
                            Документы на объект
                        </a>
                        <a href="{{ route('properties.edit', $property) }}" class="btn block text-center">
                            Редактировать
                        </a>
                        <form method="POST" action="{{ route('properties.destroy', $property) }}" class="delete-form" data-type="объявление" data-name="{{ $property->nazvanie }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn block w-full text-center hover:bg-red-600 hover:border-red-600 hover:text-white">
                                Удалить
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @endauth
    </div>
</div>

@if($canManage)
    @include('partials.status-version-history', ['versions' => $statusVersions ?? []])
@endif

<script>
// Обработка форм избранного на странице просмотра
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.favorite-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
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
                        button.innerHTML = '☆';
                        button.className = 'text-gray-400 hover:text-yellow-500 text-2xl';
                        button.title = 'Добавить в избранное';
                        const methodInput = this.querySelector('input[name="_method"]');
                        if (methodInput) {
                            methodInput.remove();
                        }
                    } else {
                        button.innerHTML = '★';
                        button.className = 'text-yellow-500 hover:text-yellow-600 text-2xl';
                        button.title = 'Удалить из избранного';
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
