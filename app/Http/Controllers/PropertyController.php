<?php

/**
 * Контроллер объявлений о недвижимости.
 *
 * Здесь обрабатываются действия пользователя с объявлениями:
 * просмотр каталога, создание, редактирование, удаление, публикация черновика.
 * Часть логики связана с модерацией: «активное» с формы обычно уходит на проверку.
 */

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Property;
use App\Models\PropertyInfoRequest;
use App\Services\AppNotifier;
use App\Models\RealtorClient;
use App\Support\ContractFormOptions;
use App\Support\RealtorScope;
use App\Support\PropertyDocumentRules;
use App\Support\PropertyFloorRules;
use App\Support\PropertyHouseAttributes;
use App\Support\PropertyCatalogFilter;
use App\Support\PropertyCatalogSimilar;
use App\Support\PropertyListingAuthor;
use App\Models\PropertyImage;
use App\Models\ZhurnalIzmeneniy;
use App\Models\PropertyStatus;
use App\Services\TextCensor;
use App\Services\YandexGeocoder;
use App\Services\PropertyOwnersService;
use App\Models\UserDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Управление объявлениями (каталог, карточка, формы, черновики).
 */
class PropertyController extends Controller
{
    /**
     * Может ли текущий пользователь менять или удалять это объявление.
     *
     * Разрешено владельцу (по полям polzovatel_id / user_id / связи user)
     * или администратору.
     */
    private function canModify(Property $property): bool
    {
        $currentId = Auth::user()->id ?? null;
        if ($currentId === null) {
            return false;
        }

        $currentId = (int) $currentId;
        $ownerIds = [];

        if (!empty($property->polzovatel_id)) {
            $ownerIds[] = (int) $property->polzovatel_id;
        }
        if (!empty($property->user_id)) {
            $ownerIds[] = (int) $property->user_id;
        }

        // Пытаемся взять владельца из связи user (lazy load при необходимости)
        $relatedUserId = optional($property->user)->id;
        if (!empty($relatedUserId)) {
            $ownerIds[] = (int) $relatedUserId;
        }

        if (in_array($currentId, $ownerIds, true)) {
            return true;
        }

        if (PropertyListingAuthor::canManage(Auth::user(), $property)) {
            return true;
        }

        // Дополнительная проверка напрямую в БД — на случай, если модель пришла без owner-полей
        if (Property::where('id', $property->id)->where('polzovatel_id', $currentId)->exists()) {
            return true;
        }

        return Auth::user()->isAdmin();
    }

    /**
     * Если в форме выбрали «активно», для новых или неактивных объявлений
     * подменяем статус на «ожидает модерации».
     *
     * Уже опубликованное (active) объявление при повторном выборе «Активно» не трогаем.
     */
    private function remapActiveToPendingReview(array &$validated, ?Property $existingProperty = null): void
    {
        if (($validated['status_obyavleniya'] ?? '') !== 'active') {
            return;
        }
        if ($existingProperty) {
            $prev = $existingProperty->status_obyavleniya ?? $existingProperty->status;
            if ($prev === 'active') {
                return;
            }
        }
        $pending = PropertyStatus::where('kod', 'pending_review')->first();
        if (!$pending) {
            return;
        }
        $validated['status_obyavleniya'] = 'pending_review';
        $validated['status_obyavleniya_id'] = $pending->id;
        $validated['prichina_otkaza_mod'] = null;
    }

    /**
     * Список объявлений в каталоге (главная витрина для всех).
     *
     * Поддерживаются поиск и фильтры из запроса (тип, операция, цена).
     */
    public function index(Request $request): View
    {
        $activeId = $this->activePropertyStatusId();
        $query = $this->catalogQuery($request);
        $properties = $query->with(['user', 'images', 'cityRelation'])->paginate(12)->withQueryString();
        $cities = $this->catalogCities($activeId);
        $this->attachFavorites($properties);

        $hasActiveFilters = PropertyCatalogSimilar::hasActiveFilters($request);
        $similarProperties = collect();
        $capturedFilters = PropertyCatalogSimilar::captureFilters($request);

        if ($properties->isEmpty() && $hasActiveFilters) {
            $similarProperties = PropertyCatalogSimilar::query($request, $activeId)
                ->with(['user', 'images', 'cityRelation'])
                ->limit(6)
                ->get();
            $this->attachFavorites($similarProperties);
        }

        return view('properties.index', compact(
            'properties',
            'cities',
            'hasActiveFilters',
            'similarProperties',
            'capturedFilters',
        ));
    }

    /**
     * Карта объявлений (Яндекс.Карты) с теми же фильтрами, что и каталог.
     */
    public function map(Request $request): View
    {
        $activeId = $this->activePropertyStatusId();
        $query = $this->catalogQuery($request);
        $allFiltered = (clone $query)->count();
        $withGeo = (clone $query)
            ->whereNotNull('geo_shirota')
            ->whereNotNull('geo_dolgota')
            ->with(['cityRelation'])
            ->get();

        $markers = $withGeo->map(fn (Property $p) => [
            'id' => $p->id,
            'lat' => (float) $p->geo_shirota,
            'lon' => (float) $p->geo_dolgota,
            'title' => $p->nazvanie,
            'price' => number_format((float) $p->tsena, 0, ',', ' ').' ₽',
            'city' => $p->gorod ?? '',
            'type' => $p->type_name,
            'url' => route('properties.show', $p),
        ])->values();

        $cities = $this->catalogCities($activeId);
        $mapApiKey = config('services.yandex_maps.api_key');

        return view('properties.map', compact('markers', 'cities', 'allFiltered', 'mapApiKey'));
    }

    private function activePropertyStatusId(): ?int
    {
        $activeId = PropertyStatus::idFor('active');
        if ($activeId === null) {
            PropertyStatus::forgetKodIdCache();
            $activeId = PropertyStatus::idFor('active');
        }

        return $activeId;
    }

    /** @return Builder<Property> */
    private function catalogQuery(Request $request): Builder
    {
        $activeId = $this->activePropertyStatusId();
        $query = Property::query();
        if ($activeId !== null) {
            $query->where('status_obyavleniya_id', $activeId);
        } else {
            $query->whereRaw('1 = 0');
        }

        PropertyCatalogFilter::apply($query, $request);

        return $query;
    }

    private function catalogCities(?int $activeId)
    {
        return City::query()
            ->when($activeId !== null, fn ($q) => $q->whereHas('properties', fn ($pq) => $pq->where('status_obyavleniya_id', $activeId)))
            ->orderBy('nazvanie')
            ->get(['id', 'nazvanie']);
    }

    private function attachFavorites($properties): void
    {
        if (!Auth::check()) {
            return;
        }
        $favoriteIds = Auth::user()->favorites()->pluck('nedvizhimost_id')->toArray();
        foreach ($properties as $property) {
            $property->is_favorite = in_array($property->id, $favoriteIds, true);
        }
    }

    /**
     * Страница формы «создать новое объявление».
     */
    public function create(): View
    {
        if (!Auth::check()) {
            abort(403, 'Необходима авторизация');
        }

        $user = Auth::user();
        $showListingAuthor = $user->isRealtor();
        $clientItems = [];

        if ($showListingAuthor) {
            $assigned = RealtorClient::query()->with('client');
            RealtorScope::forRealtor($assigned);
            $clientItems = $assigned->get()
                ->filter(fn (RealtorClient $rc) => $rc->client !== null)
                ->map(fn (RealtorClient $rc) => ContractFormOptions::userItem($rc->client))
                ->values()
                ->all();
        }

        return view('properties.create', [
            'showListingAuthor' => $showListingAuthor,
            'listingAuthorOptions' => PropertyListingAuthor::realtorOptions(),
            'clientsSearchUrl' => route('realtor.clients.search'),
            'clientItems' => $clientItems,
        ]);
    }

    /**
     * Сохранение нового объявления из формы создания.
     *
     * Проверяются поля, мат, статус, город; при необходимости загружаются фото.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge(['gorod' => trim((string) $request->input('gorod', ''))]);

        // Правила проверки данных из формы
        $validated = $request->validate([
            'nazvanie' => 'required|string|max:255',
            'opisanie' => 'required|string',
            'tip' => 'required|in:apartment,house,commercial,land',
            'operatsiya' => 'required|in:sale,rent',
            'tsena' => 'required|numeric|min:0',
            'gorod' => 'required|string|max:255',
            'adres_ulitsy' => ['required', 'string', 'max:255', 'regex:/\d/u'],
            'geo_shirota' => 'nullable|numeric|between:-90,90',
            'geo_dolgota' => 'nullable|numeric|between:-180,180',
            'ploshchad' => 'nullable|integer|min:0',
            'komnaty' => 'nullable|integer|min:0',
            'etazh' => 'nullable|integer|min:1',
            'vsego_etazhey' => 'nullable|integer|min:1',
            'status_obyavleniya' => 'nullable|in:draft,active,sold,inactive,rented,pending_review',
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,bmp|max:5120', // 5MB max per image
            ...PropertyHouseAttributes::validationRules(),
        ], [
            'gorod.required' => 'Укажите город: выберите населённый пункт из списка подсказок.',
            'adres_ulitsy.regex' => 'Укажите улицу и номер дома (в адресе должен быть номер).',
        ]);

        $validated = PropertyHouseAttributes::mergeFromRequest($request, $validated);

        // Должен быть авторизован и существовать в polzovateli
        if (!Auth::check()) {
            return redirect()->route('login')->withErrors(['auth' => 'Необходима авторизация для создания объявления']);
        }
        $user = Auth::user();
        if (!$user || !$user->id) {
            abort(401, 'Пользователь не найден');
        }

        $listing = PropertyListingAuthor::resolveFromRequest($user, $request);
        $validated['polzovatel_id'] = $listing['polzovatel_id'];
        $validated['rieltor_id'] = $listing['rieltor_id'];
        $validated['sozdal_kak'] = $listing['sozdal_kak'];
        // По умолчанию создаем как черновик, пользователь может опубликовать позже
        $validated['status_obyavleniya'] = $request->input('status_obyavleniya', 'draft');

        // Проверка названия и описания на запрещённые слова
        $profanityErrors = TextCensor::propertyFieldErrors($validated['nazvanie'], $validated['opisanie']);
        if ($profanityErrors !== []) {
            return redirect()->back()->withErrors($profanityErrors)->withInput();
        }

        $floorErrors = PropertyFloorRules::errors($validated);
        if ($floorErrors !== []) {
            return redirect()->back()->withErrors($floorErrors)->withInput();
        }

        // «Активно» с формы → на модерацию (для нового объявления)
        $this->remapActiveToPendingReview($validated, null);

        // Находим запись статуса в справочнике и пишем её id в объявление
        $status = PropertyStatus::where('kod', $validated['status_obyavleniya'])->first();
        if (!$status) {
            return redirect()->back()->withErrors(['status_obyavleniya' => 'Неверный статус объявления']);
        }
        $validated['status_obyavleniya_id'] = $status->id;

        // Город из текста формы: найти в БД или создать, сохранить gorod_id
        $city = City::firstOrCreate(['nazvanie' => $validated['gorod']]);
        $validated['gorod_id'] = $city->id;
        unset($validated['gorod'], $validated['status_obyavleniya']);

        $validated['geo_shirota'] = $request->filled('geo_shirota') ? (float) $validated['geo_shirota'] : null;
        $validated['geo_dolgota'] = $request->filled('geo_dolgota') ? (float) $validated['geo_dolgota'] : null;

        // Создание объявления и картинок в одной транзакции (всё или ничего)
        $property = DB::transaction(function () use ($request, $validated) {
            $property = Property::create($validated);

            // Загрузка до 10 изображений в папку storage
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    if ($image && $image->isValid()) {
                        $path = $image->store('properties', 'public');
                        PropertyImage::create([
                            'nedvizhimost_id' => $property->id,
                            'put_k_izobrazheniyu' => $path,
                            'poryadok' => $index,
                        ]);
                    }
                }
            }

            return $property;
        });

        PropertyOwnersService::ensureDefaultOwner($property);

        // Разные сообщения пользователю в зависимости от выбранного статуса
        if ($status->kod === 'draft') {
            return redirect()->route('properties.documents', $property)
                ->with('success', 'Черновик сохранён. Загрузите документы на объект (ЕГРН, право собственности) — без них публикация недоступна.');
        }

        if ($status->kod === 'pending_review') {
            AppNotifier::propertySubmittedForModeration($property);

            return redirect()->route('properties.index')->with('success', 'Объявление отправлено на модерацию. После проверки сотрудником оно появится в каталоге.');
        }

        return redirect()->route('properties.index')->with('success', 'Объявление сохранено.');
    }

    /**
     * Карточка одного объявления (подробный просмотр).
     *
     * Гости и чужие пользователи не видят черновики на модерации, проданные и сданные в аренду.
     */
    public function show(Property $property): View
    {
        $listingUnavailable = null;

        // Ограничения видимости для не-админов
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            $status = $property->status_obyavleniya ?? $property->status;
            $ownerId = (int) ($property->polzovatel_id ?? $property->user_id ?? 0);
            $isListingRealtor = Auth::check()
                && (int) ($property->rieltor_id ?? 0) === (int) Auth::user()->getKey();
            $isOwner = Auth::check() && ((int) Auth::user()->getKey() === $ownerId || $isListingRealtor);
            $staff = Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isRealtor());

            // На модерации — видят только автор, админ и риелтор
            if ($status === 'pending_review' && !$isOwner && !$staff) {
                abort(404, 'Объявление на модерации');
            }

            // Продано / сдано — карточка доступна для просмотра, но без новых заявок
            if ($status === 'sold' && !$isOwner && !$staff) {
                $listingUnavailable = 'sold';
            } elseif ($status === 'rented' && !$isOwner && !$staff) {
                $listingUnavailable = 'rented';
            }
        }
        
        $property->load(['user', 'realtor', 'images', 'cityRelation']);
        
        // Проверяем, добавлено ли в избранное
        if (Auth::check()) {
            $property->is_favorite = Auth::user()->hasFavorite($property);
        }

        // Журнал изменений — только владельцу объявления или админу
        $istoriyaZhurnala = collect();
        if (Auth::check()) {
            $viewer = Auth::user();
            $ownerId = (int) ($property->polzovatel_id ?? $property->user_id ?? 0);
            if ($viewer->isAdmin() || (int) $viewer->id === $ownerId) {
                $istoriyaZhurnala = ZhurnalIzmeneniy::istoriyaDlyaNedvizhimosti($property);
            }
        }

        // Координаты для карты: сначала из адреса через Яндекс.Геокодер
        $mapLat = null;
        $mapLon = null;
        $mapAddressQuery = trim(implode(', ', array_filter([
            (string) ($property->gorod ?? ''),
            (string) ($property->adres_ulitsy ?? ''),
            'Россия',
        ])));
        if ($mapAddressQuery !== '') {
            $coords = app(YandexGeocoder::class)->coordinatesForQuery($mapAddressQuery);
            if ($coords !== null) {
                $mapLat = $coords['lat'];
                $mapLon = $coords['lon'];
            }
        }

        $ownerId = (int) ($property->polzovatel_id ?? 0);
        $profileVerifiedTips = $ownerId > 0
            ? \App\Support\UserProfileDocuments::verifiedTips($ownerId)
            : [];

        $canManage = Auth::check()
            && PropertyListingAuthor::canManage(Auth::user(), $property);

        $docsReady = PropertyDocumentRules::isReadyForPublication($property);
        $st = $property->status_obyavleniya ?? $property->status;
        $canPublishToModeration = $canManage
            && $st === 'draft'
            && $docsReady;

        $statusVersions = collect();
        if (Auth::check() && PropertyListingAuthor::canManage(Auth::user(), $property)) {
            $statusVersions = \App\Services\ProcessVersionService::history('property', (int) $property->id);
        }

        $isActiveListing = ($property->status_obyavleniya ?? $property->status ?? '') === 'active';
        $canAskInfo = Auth::check()
            && $isActiveListing
            && (int) Auth::id() !== (int) ($property->polzovatel_id ?? 0);

        $infoRequests = collect();
        if (Auth::check()) {
            $infoQuery = PropertyInfoRequest::with(['messages.user'])
                ->where('nedvizhimost_id', $property->id);
            if (!Auth::user()->isStaff()) {
                $infoQuery->where('polzovatel_id', Auth::id());
            }
            $infoRequests = $infoQuery->orderByDesc('sozdano_at')->limit(15)->get();
        }

        return view('properties.show', compact(
            'property',
            'istoriyaZhurnala',
            'statusVersions',
            'mapLat',
            'mapLon',
            'mapAddressQuery',
            'docsReady',
            'profileVerifiedTips',
            'canPublishToModeration',
            'canManage',
            'canAskInfo',
            'infoRequests',
            'listingUnavailable',
        ));
    }

    /**
     * Страница формы редактирования объявления.
     */
    public function edit(Property $property): View
    {
        // Только владелец или админ
        if (!$this->canModify($property)) {
            abort(403);
        }

        $property->load(['images', 'owners.user']);
        $canManage = true;

        return view('properties.edit', compact('property', 'canManage'));
    }

    /**
     * Сохранение изменений объявления из формы редактирования.
     *
     * Можно сменить статус, обновить поля, удалить и добавить фотографии.
     */
    public function update(Request $request, Property $property): RedirectResponse
    {
        // Только владелец или админ
        if (!$this->canModify($property)) {
            abort(403);
        }

        $request->merge(['gorod' => trim((string) $request->input('gorod', ''))]);

        $validated = $request->validate([
            'nazvanie' => 'required|string|max:255',
            'opisanie' => 'required|string',
            'tip' => 'required|in:apartment,house,commercial,land',
            'operatsiya' => 'required|in:sale,rent',
            'tsena' => 'required|numeric|min:0',
            'gorod' => 'required|string|max:255',
            'adres_ulitsy' => ['required', 'string', 'max:255', 'regex:/\d/u'],
            'geo_shirota' => 'nullable|numeric|between:-90,90',
            'geo_dolgota' => 'nullable|numeric|between:-180,180',
            'ploshchad' => 'nullable|integer|min:0',
            'komnaty' => 'nullable|integer|min:0',
            'etazh' => 'nullable|integer|min:1',
            'vsego_etazhey' => 'nullable|integer|min:1',
            'status_obyavleniya' => 'required|in:draft,active,sold,inactive,rented,pending_review',
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,bmp|max:5120', // 5MB max per image
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'exists:izobrazheniya_nedvizhimosti,id',
            ...PropertyHouseAttributes::validationRules(),
        ], [
            'gorod.required' => 'Укажите город: выберите населённый пункт из списка подсказок.',
            'adres_ulitsy.regex' => 'Укажите улицу и номер дома (в адресе должен быть номер).',
            'status_obyavleniya.required' => 'Выберите статус объявления.',
            'status_obyavleniya.in' => 'Выбран недопустимый статус объявления.',
        ], [
            'status_obyavleniya' => 'статус объявления',
        ]);

        $validated = PropertyHouseAttributes::mergeFromRequest($request, $validated);

        // Проверка названия и описания на запрещённые слова
        $profanityErrors = TextCensor::propertyFieldErrors($validated['nazvanie'], $validated['opisanie']);
        if ($profanityErrors !== []) {
            return redirect()->back()->withErrors($profanityErrors)->withInput();
        }

        $floorErrors = PropertyFloorRules::errors($validated);
        if ($floorErrors !== []) {
            return redirect()->back()->withErrors($floorErrors)->withInput();
        }

        // При выборе «активно» снова отправляем на модерацию, если ещё не в каталоге
        $this->remapActiveToPendingReview($validated, $property);

        $status = PropertyStatus::where('kod', $validated['status_obyavleniya'])->first();
        if (!$status) {
            return redirect()->back()->withErrors(['status_obyavleniya' => 'Неверный статус объявления']);
        }
        $validated['status_obyavleniya_id'] = $status->id;

        // Город: найти или создать запись, подставить gorod_id
        $city = City::firstOrCreate(['nazvanie' => $validated['gorod']]);
        $validated['gorod_id'] = $city->id;
        unset($validated['gorod'], $validated['status_obyavleniya']);

        $validated['geo_shirota'] = $request->filled('geo_shirota') ? (float) $validated['geo_shirota'] : null;
        $validated['geo_dolgota'] = $request->filled('geo_dolgota') ? (float) $validated['geo_dolgota'] : null;

        // Повторная отправка на модерацию — сбрасываем старую причину отказа
        if ($status->kod === 'pending_review') {
            $validated['prichina_otkaza_mod'] = null;
        }

        $oldStatus = $property->status_obyavleniya ?? $property->status;

        $property->update($validated);
        $property->refresh();

        if ($status->kod === 'pending_review' && $oldStatus !== 'pending_review') {
            AppNotifier::propertySubmittedForModeration($property);
        }

        // Удаление выбранных фотографий
        if ($request->has('delete_images')) {
            foreach ($request->delete_images as $imageId) {
                $image = PropertyImage::find($imageId);
                if ($image && ($image->nedvizhimost_id ?? $image->property_id) === $property->id) {
                    // Удаляем файл из storage
                    $imagePath = $image->put_k_izobrazheniyu ?? $image->image_path;
                    if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                    }
                    // Удаляем запись из БД
                    $image->delete();
                }
            }
        }

        // Добавление новых фотографий
        if ($request->hasFile('images')) {
            $existingImagesCount = $property->images()->count();
            foreach ($request->file('images') as $index => $image) {
                if ($image && $image->isValid()) {
                    $path = $image->store('properties', 'public');
                    PropertyImage::create([
                        'nedvizhimost_id' => $property->id,
                        'put_k_izobrazheniyu' => $path,
                        'poryadok' => $existingImagesCount + $index,
                    ]);
                }
            }
        }

        // Сообщение после сохранения: отдельный текст, если снова ушло на модерацию
        $msg = 'Объявление обновлено';
        $newSt = $property->status_obyavleniya ?? $property->status;
        if ($newSt === 'pending_review') {
            $msg = 'Изменения сохранены. Объявление отправлено на модерацию.';
        }

        return redirect()->route('properties.show', $property)->with('success', $msg);
    }

    /**
     * Удаление объявления и всех его файлов с диска.
     */
    public function destroy(Property $property): RedirectResponse
    {
        // Только владелец или админ
        if (!$this->canModify($property)) {
            abort(403);
        }

        // Удаляем все фотографии
        foreach ($property->images as $image) {
            $imagePath = $image->put_k_izobrazheniyu ?? $image->image_path;
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        $property->delete();

        return redirect()->route('properties.index')->with('success', 'Объявление удалено');
    }

    /**
     * Отправить черновик на модерацию (кнопка «опубликовать» у черновика).
     *
     * Сразу в каталог объявление не попадает — ждёт решения модератора.
     */
    public function publish(Property $property): RedirectResponse
    {
        // Только владелец или админ
        if (!$this->canModify($property)) {
            abort(403);
        }

        $status = $property->status_obyavleniya ?? $property->status;
        if ($status !== 'draft') {
            return redirect()->back()->withErrors(['error' => 'Отправить на модерацию можно только черновик']);
        }

        $profanityErrors = TextCensor::propertyFieldErrors($property->nazvanie, $property->opisanie);
        if ($profanityErrors !== []) {
            return redirect()->back()->withErrors([
                'error' => TextCensor::propertyRejectionMessage(),
            ]);
        }

        if (!PropertyDocumentRules::isReadyForPublication($property)) {
            $missing = PropertyDocumentRules::missingLabels($property);

            return redirect()->route('properties.documents', $property)->withErrors([
                'error' => 'Для публикации загрузите и дождитесь проверки документов: ' . implode('; ', $missing),
            ]);
        }

        $pending = PropertyStatus::where('kod', 'pending_review')->firstOrFail();
        $property->update([
            'status_obyavleniya_id' => $pending->id,
            'prichina_otkaza_mod' => null,
        ]);

        AppNotifier::propertySubmittedForModeration($property);

        return redirect()->route('properties.show', $property)->with('success', 'Объявление отправлено на модерацию.');
    }

    /**
     * Список черновиков текущего пользователя (личный кабинет).
     */
    public function drafts(): View
    {
        if (!Auth::check()) {
            abort(403, 'Необходима авторизация');
        }

        // Только объявления со статусом «черновик» и принадлежащие этому пользователю
        $draftId = PropertyStatus::idFor('draft');
        $drafts = Property::where('polzovatel_id', Auth::user()->getKey());
        if ($draftId !== null) {
            $drafts->where('status_obyavleniya_id', $draftId);
        } else {
            $drafts->whereRaw('1 = 0'); // статус черновика не найден
        }
        $drafts = $drafts->latest()->paginate(10);

        return view('properties.drafts', compact('drafts'));
    }
}

