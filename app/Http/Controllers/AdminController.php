<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Property;
use App\Models\ZhurnalIzmeneniy;
use App\Models\PropertyImage;
use App\Models\PropertyStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\TextCensor;
use App\Support\ContractFormOptions;
use App\Support\EnsureContractPartiesSchema;
use App\Support\PropertyFloorRules;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Панель администратора: дашборд, пользователи, объявления, договоры.
 * Все действия доступны только пользователю с ролью admin.
 */
class AdminController extends Controller
{
    /**
     * Проверка прав администратора
     */
    private function checkAdmin()
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Доступ запрещен');
        }
    }

    /**
     * Своей учётной записью нельзя управлять (блокировка / удаление).
     * Важно: у User переопределён getAuthIdentifierName() → email, поэтому Auth::id() — это email, а не id строки в БД.
     */
    private function isAdminEditingSelf(User $user): bool
    {
        $auth = Auth::user();
        if (!$auth instanceof User) {
            return false;
        }

        return (int) $auth->getKey() === (int) $user->getKey();
    }

    /**
     * Главная страница админки: счётчики и последние записи.
     */
    public function index(): View
    {
        $this->checkAdmin();

        // Сводные цифры по справочникам статусов (idFor — id строки в таблице статусов)
        $stats = [
            'total_users' => User::count(),
            'total_properties' => Property::count(),
            'active_properties' => ($aid = PropertyStatus::idFor('active')) !== null ? Property::where('status_obyavleniya_id', $aid)->count() : 0,
            'pending_moderation' => ($pid = PropertyStatus::idFor('pending_review')) !== null ? Property::where('status_obyavleniya_id', $pid)->count() : 0,
            'sold_properties' => ($sid = PropertyStatus::idFor('sold')) !== null ? Property::where('status_obyavleniya_id', $sid)->count() : 0,
            'total_contracts' => Contract::count(),
            'active_contracts' => ($cid = ContractStatus::idFor('active')) !== null ? Contract::where('status_dogovora_id', $cid)->count() : 0,
            'realtors' => User::whereHas('roleRelation', fn ($q) => $q->where('kod', 'realtor'))->count(),
            'clients' => User::whereHas('roleRelation', fn ($q) => $q->where('kod', 'client'))->count(),
        ];

        $recent_properties = Property::with('user')->latest()->take(10)->get();
        $recent_users = User::latest()->take(10)->get();
        $recent_contracts = Contract::with(['property', 'owner', 'buyer', 'client', 'realtor'])->latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'recent_properties', 'recent_users', 'recent_contracts'));
    }

    // ==================== Пользователи ====================

    /**
     * Список пользователей с поиском и пагинацией.
     */
    public function users(Request $request): View
    {
        $this->checkAdmin();
        
        $query = User::withCount('properties');
        
        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('familia', 'like', "%{$search}%")
                  ->orWhere('imya', 'like', "%{$search}%")
                  ->orWhere('otchestvo', 'like', "%{$search}%")
                  ->orWhere('email_polzovatela', 'like', "%{$search}%")
                  ->orWhere('telefon', 'like', "%{$search}%");
            });
        }
        
        $users = $query->latest()->paginate(20)->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Форма создания пользователя.
     */
    public function createUser(): View
    {
        $this->checkAdmin();
        
        return view('admin.users.create');
    }

    /**
     * Сохранение нового пользователя (роль подтягивается или создаётся в справочнике).
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $this->checkAdmin();
        
        $validated = $request->validate([
            'familia' => 'required|string|max:255',
            'imya' => 'required|string|max:255',
            'otchestvo' => 'nullable|string|max:255',
            'email_polzovatela' => 'required|string|lowercase|email|max:255|unique:polzovateli,email_polzovatela',
            'parol' => 'required|string|min:8|confirmed',
            'telefon' => 'nullable|string|max:20',
            'rol' => 'required|in:admin,realtor,client',
        ]);

        $role = Role::firstOrCreate(
            ['kod' => $validated['rol']],
            ['nazvanie' => ucfirst($validated['rol'])]
        );

        User::create([
            'familia' => $validated['familia'],
            'imya' => $validated['imya'],
            'otchestvo' => $validated['otchestvo'] ?? null,
            'email_polzovatela' => $validated['email_polzovatela'],
            'parol' => Hash::make($validated['parol']),
            'telefon' => $validated['telefon'] ?? null,
            'rol_id' => $role->id,
        ]);

        return redirect()->route('admin.users')->with('success', 'Пользователь создан успешно');
    }

    /**
     * Форма редактирования пользователя.
     */
    public function editUser(User $user): View
    {
        $this->checkAdmin();
        
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Обновление данных пользователя; пароль меняется только если поле заполнено.
     */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $this->checkAdmin();
        
        // Запрещаем админу изменять свою роль
        $currentRole = $user->role;
        if ($this->isAdminEditingSelf($user) && $request->rol !== $currentRole) {
            return redirect()->back()->with('error', 'Вы не можете изменить свою роль');
        }
        
        $validated = $request->validate([
            'familia' => 'required|string|max:255',
            'imya' => 'required|string|max:255',
            'otchestvo' => 'nullable|string|max:255',
            'email_polzovatela' => 'required|string|lowercase|email|max:255|unique:polzovateli,email_polzovatela,' . $user->id,
            'parol' => 'nullable|string|min:8|confirmed',
            'telefon' => 'nullable|string|max:20',
            'rol' => 'required|in:admin,realtor,client',
        ]);

        $role = Role::firstOrCreate(
            ['kod' => $validated['rol']],
            ['nazvanie' => ucfirst($validated['rol'])]
        );

        $updateData = [
            'familia' => $validated['familia'],
            'imya' => $validated['imya'],
            'otchestvo' => $validated['otchestvo'] ?? null,
            'email_polzovatela' => $validated['email_polzovatela'],
            'telefon' => $validated['telefon'] ?? null,
            'rol_id' => $role->id,
        ];

        if (!empty($validated['parol'])) {
            $updateData['parol'] = Hash::make($validated['parol']);
        }

        $user->update($updateData);

        return redirect()->route('admin.users')->with('success', 'Пользователь обновлен');
    }

    /**
     * Удаление пользователя (себя удалить нельзя).
     */
    public function deleteUser(User $user): RedirectResponse
    {
        $this->checkAdmin();

        if ($this->isAdminEditingSelf($user)) {
            return redirect()->back()->with('error', 'Вы не можете удалить свой аккаунт');
        }

        $user->delete();
        return redirect()->back()->with('success', 'Пользователь удален');
    }

    /**
     * Блокировка пользователя.
     */
    public function blockUser(User $user): RedirectResponse
    {
        $this->checkAdmin();

        if ($this->isAdminEditingSelf($user)) {
            return redirect()->back()->with('error', 'Вы не можете заблокировать свой аккаунт');
        }

        $user->block();
        return redirect()->back()->with('success', 'Пользователь заблокирован');
    }

    /**
     * Разблокировка пользователя.
     */
    public function unblockUser(User $user): RedirectResponse
    {
        $this->checkAdmin();
        
        $user->unblock();
        return redirect()->back()->with('success', 'Пользователь разблокирован');
    }

    // ==================== Объявления (недвижимость) ====================

    /**
     * Список объявлений с поиском по названию, описанию, городу и адресу.
     */
    public function properties(Request $request): View
    {
        $this->checkAdmin();
        
        $query = Property::with(['user', 'cityRelation']);
        
        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nazvanie', 'like', "%{$search}%")
                    ->orWhere('opisanie', 'like', "%{$search}%")
                    ->orWhereHas('cityRelation', function ($cq) use ($search) {
                        $cq->where('nazvanie', 'like', "%{$search}%");
                    })
                    ->orWhere('adres_ulitsy', 'like', "%{$search}%");
            });
        }
        
        $properties = $query->latest()->paginate(20)->withQueryString();

        return view('admin.properties.index', [
            'properties' => $properties,
            'propertyStatuses' => PropertyStatus::orderBy('id')->get(),
        ]);
    }

    /**
     * Форма создания объявления; владельцем может быть риэлтор или админ.
     */
    public function createProperty(): View
    {
        $this->checkAdmin();
        
        $users = User::whereHas('roleRelation', fn ($q) => $q->whereIn('kod', ['realtor', 'admin']))->get();
        return view('admin.properties.create', compact('users'));
    }

    /**
     * Создание объявления: проверка мата, статус и город, загрузка до 10 фото.
     */
    public function storeProperty(Request $request): RedirectResponse
    {
        $this->checkAdmin();

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
            'polzovatel_id' => 'required|exists:polzovateli,id',
            'status_obyavleniya' => 'required|in:draft,active,pending_review,sold,inactive,rented',
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,bmp|max:5120', // 5MB max per image
        ], [
            'gorod.required' => 'Укажите город: выберите населённый пункт из списка подсказок.',
            'adres_ulitsy.regex' => 'Укажите улицу и номер дома (в адресе должен быть номер).',
        ]);

        $profanityErrors = TextCensor::propertyFieldErrors($validated['nazvanie'], $validated['opisanie']);
        if ($profanityErrors !== []) {
            return redirect()->back()->withErrors($profanityErrors)->withInput();
        }

        $floorErrors = PropertyFloorRules::errors($validated);
        if ($floorErrors !== []) {
            return redirect()->back()->withErrors($floorErrors)->withInput();
        }

        // Получаем ID статуса
        $status = PropertyStatus::where('kod', $validated['status_obyavleniya'])->first();
        if (!$status) {
            return redirect()->back()->withErrors(['status_obyavleniya' => 'Неверный статус объявления']);
        }
        $validated['status_obyavleniya_id'] = $status->id;

        // Получаем или создаем город
        $city = City::firstOrCreate(['nazvanie' => $validated['gorod']]);
        $validated['gorod_id'] = $city->id;
        unset($validated['gorod'], $validated['status_obyavleniya']);

        $validated['geo_shirota'] = $request->filled('geo_shirota') ? (float) $validated['geo_shirota'] : null;
        $validated['geo_dolgota'] = $request->filled('geo_dolgota') ? (float) $validated['geo_dolgota'] : null;

        $property = Property::create($validated);

        // Сохранение фотографий
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                if ($image && $image->isValid()) {
                    try {
                        $path = $image->store('properties', 'public');
                        PropertyImage::create([
                            'nedvizhimost_id' => $property->id,
                            'put_k_izobrazheniyu' => $path,
                            'poryadok' => $index,
                        ]);
                    } catch (\Exception $e) {
                        // Логируем ошибку, но продолжаем создание объявления
                        \Log::error('Ошибка при сохранении изображения: ' . $e->getMessage());
                    }
                }
            }
        }

        return redirect()->route('admin.properties')->with('success', 'Объявление создано успешно');
    }

    /**
     * Форма редактирования объявления и журнал изменений по объекту.
     */
    public function editProperty(Property $property): View
    {
        $this->checkAdmin();
        
        $property->load('images');
        $users = User::whereHas('roleRelation', fn ($q) => $q->whereIn('kod', ['realtor', 'admin']))->get();
        $istoriyaZhurnala = ZhurnalIzmeneniy::istoriyaDlyaNedvizhimosti($property);

        return view('admin.properties.edit', compact('property', 'users', 'istoriyaZhurnala'));
    }

    /**
     * Обновление объявления: можно удалить старые фото и добавить новые.
     */
    public function updateProperty(Request $request, Property $property): RedirectResponse
    {
        $this->checkAdmin();

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
            'polzovatel_id' => 'required|exists:polzovateli,id',
            'status_obyavleniya' => 'required|in:draft,active,pending_review,sold,inactive,rented',
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,bmp|max:5120', // 5MB max per image
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'exists:izobrazheniya_nedvizhimosti,id',
        ], [
            'gorod.required' => 'Укажите город: выберите населённый пункт из списка подсказок.',
            'adres_ulitsy.regex' => 'Укажите улицу и номер дома (в адресе должен быть номер).',
        ]);

        $profanityErrors = TextCensor::propertyFieldErrors($validated['nazvanie'], $validated['opisanie']);
        if ($profanityErrors !== []) {
            return redirect()->back()->withErrors($profanityErrors)->withInput();
        }

        $floorErrors = PropertyFloorRules::errors($validated);
        if ($floorErrors !== []) {
            return redirect()->back()->withErrors($floorErrors)->withInput();
        }

        // Получаем ID статуса
        $status = PropertyStatus::where('kod', $validated['status_obyavleniya'])->first();
        if (!$status) {
            return redirect()->back()->withErrors(['status_obyavleniya' => 'Неверный статус объявления']);
        }
        $validated['status_obyavleniya_id'] = $status->id;

        // Получаем или создаем город
        $city = City::firstOrCreate(['nazvanie' => $validated['gorod']]);
        $validated['gorod_id'] = $city->id;
        unset($validated['gorod'], $validated['status_obyavleniya']);

        $validated['geo_shirota'] = $request->filled('geo_shirota') ? (float) $validated['geo_shirota'] : null;
        $validated['geo_dolgota'] = $request->filled('geo_dolgota') ? (float) $validated['geo_dolgota'] : null;

        // При одобрении модерации сбрасываем причину отказа
        if ($status->kod === 'active') {
            $validated['prichina_otkaza_mod'] = null;
        }

        $property->update($validated);

        // Удаление выбранных фотографий
        if ($request->has('delete_images')) {
            foreach ($request->delete_images as $imageId) {
                $image = PropertyImage::find($imageId);
                $imagePropertyId = $image->nedvizhimost_id ?? $image->property_id;
                if ($image && $imagePropertyId === $property->id) {
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

        // Новые фото — порядок продолжается после уже загруженных
        if ($request->hasFile('images')) {
            $existingImagesCount = $property->images()->count();
            foreach ($request->file('images') as $index => $image) {
                if ($image && $image->isValid()) {
                    try {
                        $path = $image->store('properties', 'public');
                        PropertyImage::create([
                            'nedvizhimost_id' => $property->id,
                            'put_k_izobrazheniyu' => $path,
                            'poryadok' => $existingImagesCount + $index,
                        ]);
                    } catch (\Exception $e) {
                        // Логируем ошибку, но продолжаем обновление объявления
                        \Log::error('Ошибка при сохранении изображения: ' . $e->getMessage());
                    }
                }
            }
        }

        return redirect()->route('admin.properties')->with('success', 'Объявление обновлено');
    }

    /**
     * Удаление объявления вместе с файлами фотографий на диске.
     */
    public function deleteProperty(Property $property): RedirectResponse
    {
        $this->checkAdmin();
        
        // Удаляем все фотографии
        foreach ($property->images as $image) {
            $imagePath = $image->put_k_izobrazheniyu ?? $image->image_path;
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }
        
        $property->delete();
        return redirect()->back()->with('success', 'Объявление удалено');
    }

    // ==================== Договоры ====================

    /**
     * Список договоров с поиском по объекту, сторонам и примечаниям.
     */
    public function contracts(Request $request): View
    {
        $this->checkAdmin();
        EnsureContractPartiesSchema::apply();

        $query = Contract::with(['property', 'owner', 'buyer', 'client', 'realtor']);
        
        // Поиск по объекту, клиенту, риэлтору и тексту примечаний (учтены старые имена полей)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('property', function($q) use ($search) {
                    $q->where('nazvanie', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
                })
                ->orWhereHas('client', function($q) use ($search) {
                    $q->where(function($subQ) use ($search) {
                        $subQ->where('familia', 'like', "%{$search}%")
                             ->orWhere('imya', 'like', "%{$search}%")
                             ->orWhere('otchestvo', 'like', "%{$search}%")
                             ->orWhere('email_polzovatela', 'like', "%{$search}%")
                             ->orWhere('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orWhereHas('realtor', function($q) use ($search) {
                    $q->where(function($subQ) use ($search) {
                        $subQ->where('familia', 'like', "%{$search}%")
                             ->orWhere('imya', 'like', "%{$search}%")
                             ->orWhere('otchestvo', 'like', "%{$search}%")
                             ->orWhere('email_polzovatela', 'like', "%{$search}%")
                             ->orWhere('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orWhere('primechaniya', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        
        $contracts = $query->latest()->paginate(20)->withQueryString();

        return view('admin.contracts.index', [
            'contracts' => $contracts,
            'contractStatuses' => ContractStatus::orderBy('id')->get(),
        ]);
    }

    /**
     * Форма нового договора: только активные объявления продажи/аренды.
     */
    public function createContract(): View
    {
        $this->checkAdmin();
        
        $properties = ContractFormOptions::activePropertiesQuery()
            ->with('cityRelation')
            ->orderBy('nazvanie')
            ->get();
        $clients = User::whereHas('roleRelation', fn ($q) => $q->where('kod', 'client'))
            ->orderBy('familia')->orderBy('imya')->get();
        $realtors = User::whereHas('roleRelation', fn ($q) => $q->whereIn('kod', ['realtor', 'admin']))
            ->orderBy('familia')->orderBy('imya')->get();

        $propertyItems = $properties->map(fn (Property $p) => ContractFormOptions::propertyItem($p))->values()->all();
        $clientItems = $clients->map(fn (User $u) => ContractFormOptions::userItem($u))->values()->all();
        $realtorItems = $realtors->map(fn (User $u) => ContractFormOptions::userItem($u))->values()->all();

        return view('admin.contracts.create', compact(
            'properties',
            'clients',
            'realtors',
            'propertyItems',
            'clientItems',
            'realtorItems'
        ));
    }

    /**
     * Создание договора; тип (sale/rent) должен совпадать с объявлением.
     */
    public function storeContract(Request $request): RedirectResponse
    {
        $this->checkAdmin();
        EnsureContractPartiesSchema::apply();

        $validated = $request->validate([
            'nedvizhimost_id' => 'required|exists:nedvizhimost,id',
            'vladelets_id' => 'required|exists:polzovateli,id',
            'pokupatel_id' => 'required|exists:polzovateli,id|different:vladelets_id',
            'rieltor_id' => 'required|exists:polzovateli,id',
            'tip' => 'required|in:sale,rent',
            'tsena' => 'required|numeric|min:0',
            'data_nachala' => 'required|date',
            'data_okonchaniya' => 'nullable|date|after_or_equal:data_nachala|required_if:tip,rent',
            'status_dogovora' => 'required|in:draft,pending,active,completed,cancelled',
            'primechaniya' => 'nullable|string',
        ]);

        if (!empty($validated['primechaniya'])) {
            $noteErrors = TextCensor::fieldError('primechaniya', $validated['primechaniya']);
            if ($noteErrors !== []) {
                return redirect()->back()->withErrors($noteErrors)->withInput();
            }
        }

        $prop = Property::findOrFail($validated['nedvizhimost_id']);
        if (($prop->operatsiya ?? $prop->operation) !== $validated['tip']) {
            return redirect()->back()->withErrors(['tip' => 'Тип договора должен совпадать с объявлением (продажа или аренда).'])->withInput();
        }

        // У продажи нет даты окончания
        if ($validated['tip'] === 'sale') {
            $validated['data_okonchaniya'] = null;
        }

        $statusRow = ContractStatus::where('kod', $validated['status_dogovora'])->first();
        if ($statusRow) {
            $validated['status_dogovora_id'] = $statusRow->id;
        }
        unset($validated['status_dogovora']);

        $validated['klient_id'] = $validated['pokupatel_id'];
        $validated['sozdal_kak'] = 'realtor';
        $validated['sozdal_storona'] = null;
        $validated['ozhidaet_podtverzhdeniya'] = null;

        if ($statusRow && $statusRow->kod === 'active') {
            $now = now();
            $validated['podtverzhden_vladelets_at'] = $now;
            $validated['podtverzhden_pokupatel_at'] = $now;
            $validated['podtverzhden_rieltor_at'] = $now;
        }

        Contract::create($validated);

        return redirect()->route('admin.contracts')->with('success', 'Договор создан успешно');
    }

    /**
     * Форма редактирования договора; в списке объектов остаётся и текущий, даже если не «активен».
     */
    public function editContract(Contract $contract): View
    {
        $this->checkAdmin();
        EnsureContractPartiesSchema::apply();

        $pid = $contract->nedvizhimost_id ?? $contract->property_id;
        $activePid = PropertyStatus::idFor('active');
        // Активные + объект, уже привязанный к этому договору
        $properties = Property::whereIn('operatsiya', ['sale', 'rent'])
            ->where(function ($q) use ($activePid, $pid) {
                if ($activePid !== null) {
                    $q->where('status_obyavleniya_id', $activePid);
                }
                $q->orWhere('id', $pid);
            })
            ->get();
        $clients = User::whereHas('roleRelation', fn ($q) => $q->where('kod', 'client'))->get();
        $realtors = User::whereHas('roleRelation', fn ($q) => $q->whereIn('kod', ['realtor', 'admin']))->get();

        return view('admin.contracts.edit', compact('contract', 'properties', 'clients', 'realtors'));
    }

    /**
     * Обновление договора; скан PDF/фото только для аренды.
     */
    public function updateContract(Request $request, Contract $contract): RedirectResponse
    {
        $this->checkAdmin();
        EnsureContractPartiesSchema::apply();

        $validated = $request->validate([
            'nedvizhimost_id' => 'required|exists:nedvizhimost,id',
            'vladelets_id' => 'required|exists:polzovateli,id',
            'pokupatel_id' => 'required|exists:polzovateli,id|different:vladelets_id',
            'rieltor_id' => 'required|exists:polzovateli,id',
            'tip' => 'required|in:sale,rent',
            'tsena' => 'required|numeric|min:0',
            'data_nachala' => 'required|date',
            'data_okonchaniya' => 'nullable|date|after_or_equal:data_nachala|required_if:tip,rent',
            'status_dogovora' => 'required|in:draft,pending,active,completed,cancelled',
            'primechaniya' => 'nullable|string',
        ]);

        if (!empty($validated['primechaniya'])) {
            $noteErrors = TextCensor::fieldError('primechaniya', $validated['primechaniya']);
            if ($noteErrors !== []) {
                return redirect()->back()->withErrors($noteErrors)->withInput();
            }
        }

        $prop = Property::findOrFail($validated['nedvizhimost_id']);
        if (($prop->operatsiya ?? $prop->operation) !== $validated['tip']) {
            return redirect()->back()->withErrors(['tip' => 'Тип договора должен совпадать с объявлением (продажа или аренда).'])->withInput();
        }

        // При смене на продажу убираем дату окончания и файл скана аренды
        if ($validated['tip'] === 'sale') {
            $validated['data_okonchaniya'] = null;
            if ($contract->skan_dogovora) {
                Storage::disk('public')->delete($contract->skan_dogovora);
            }
            $validated['skan_dogovora'] = null;
        }

        $statusRow = ContractStatus::where('kod', $validated['status_dogovora'])->first();
        if ($statusRow) {
            $validated['status_dogovora_id'] = $statusRow->id;
        }
        unset($validated['status_dogovora']);

        if ($request->hasFile('skan_dogovora')) {
            $request->validate([
                'skan_dogovora' => 'file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            ]);
            if ($contract->skan_dogovora) {
                Storage::disk('public')->delete($contract->skan_dogovora);
            }
            $validated['skan_dogovora'] = $request->file('skan_dogovora')->store('contract_scans/'.$contract->id, 'public');
        }

        $validated['klient_id'] = $validated['pokupatel_id'];
        if ($statusRow && $statusRow->kod === 'active') {
            $now = now();
            $validated['podtverzhden_vladelets_at'] = $contract->podtverzhden_vladelets_at ?? $now;
            $validated['podtverzhden_pokupatel_at'] = $contract->podtverzhden_pokupatel_at ?? $now;
            $validated['podtverzhden_rieltor_at'] = $contract->podtverzhden_rieltor_at ?? $now;
        }

        $contract->update($validated);

        return redirect()->route('admin.contracts')->with('success', 'Договор обновлен');
    }

    /**
     * Удаление договора.
     */
    public function deleteContract(Contract $contract): RedirectResponse
    {
        $this->checkAdmin();
        
        $contract->delete();
        return redirect()->back()->with('success', 'Договор удален');
    }

    /**
     * Скачать договор как PDF для печати или архива.
     */
    public function exportContractPdf(Contract $contract)
    {
        $this->checkAdmin();
        
        $contract->load(['property', 'owner', 'buyer', 'client', 'realtor']);
        
        $pdf = Pdf::loadView('contracts.pdf', compact('contract'));

        $filename = 'dogovor_'.$contract->id.'_'.date('Y-m-d').'.pdf';
        
        return $pdf->download($filename);
    }
}
