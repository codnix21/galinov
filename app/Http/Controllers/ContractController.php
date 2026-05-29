<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\User;
use App\Services\TextCensor;
use App\Services\AppNotifier;
use App\Services\ContractEcpService;
use App\Services\PropertyOwnersService;
use App\Support\ContractApproval;
use App\Support\ContractFormOptions;
use App\Support\EnsureContractPartiesSchema;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Договоры: владелец, покупатель, риэлтор; многостороннее подтверждение.
 */
class ContractController extends Controller
{
    private function isStaff($user): bool
    {
        return $user && ($user->isRealtor() || $user->isAdmin());
    }

    private function contractsForUserQuery($user)
    {
        return Contract::query()->where(function ($q) use ($user) {
            $q->where('vladelets_id', $user->id)
                ->orWhere('pokupatel_id', $user->id)
                ->orWhere('rieltor_id', $user->id);
        });
    }

    private function assertContractViewAccess($user, Contract $contract): void
    {
        if (!$user) {
            abort(403);
        }
        if ($this->isStaff($user) || ContractApproval::userIsParty($contract, $user)) {
            return;
        }

        abort(404, 'Договор не найден или у вас нет доступа к этой сделке.');
    }

    /** Скан подписанного договора загружают риэлтор или администратор */
    private function canUploadContractScan($user): bool
    {
        return $user && ($user->isRealtor() || $user->isAdmin());
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        $search = trim((string) $request->input('q', ''));
        $sort = (string) $request->input('sort', 'newest');
        $dir = $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        if ($user->isClient()) {
            $query = $this->contractsForUserQuery($user);
        } elseif ($this->isStaff($user)) {
            $query = Contract::query();
        } else {
            $query = Contract::whereRaw('1 = 0');
        }

        $query->with(['property', 'owner', 'buyer', 'realtor', 'statusRelation']);

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                if (ctype_digit($search)) {
                    $sub->orWhere('dogovory.id', (int) $search);
                }
                $like = '%'.$search.'%';
                $sub->orWhereHas('property', fn ($p) => $p->where('nazvanie', 'like', $like));
                $sub->orWhereHas('owner', fn ($u) => $this->applyUserSearch($u, $like));
                $sub->orWhereHas('buyer', fn ($u) => $this->applyUserSearch($u, $like));
                $sub->orWhereHas('realtor', fn ($u) => $this->applyUserSearch($u, $like));
            });
        }

        match ($sort) {
            'price' => $query->orderBy('tsena', $dir),
            'date_start' => $query->orderBy('data_nachala', $dir),
            'id' => $query->orderBy('id', $dir),
            default => $query->orderBy('sozdano_at', $dir),
        };

        $contracts = $query->paginate(10)->withQueryString();

        return view('contracts.index', compact('contracts', 'search', 'sort', 'dir'));
    }

    private function applyUserSearch($query, string $like): void
    {
        $query->where(function ($u) use ($like) {
            $u->where('familia', 'like', $like)
                ->orWhere('imya', 'like', $like)
                ->orWhere('otchestvo', 'like', $like)
                ->orWhere('email_polzovatela', 'like', $like);
        });
    }

    public function create(?Property $property = null): View|RedirectResponse
    {
        $user = Auth::user();

        if (!$user->isClient() && !$this->isStaff($user)) {
            abort(403, 'Доступ разрешен только клиентам и риэлторам');
        }

        if ($user->isClient()) {
            return redirect()
                ->route('contracts.index')
                ->with('success', 'Сделку оформляйте на карточке объявления: «Купить онлайн» или «Экспресс-сделка». Ручное создание договора доступно риэлтору.');
        }

        $properties = ContractFormOptions::activePropertiesQuery()
            ->with('cityRelation')
            ->orderBy('nazvanie')
            ->get();

        if ($property) {
            $property->loadMissing('cityRelation');
            $operation = $property->operatsiya ?? $property->operation;
            if (in_array($operation, ['sale', 'rent'], true)
                && !$properties->contains(fn (Property $p) => (int) $p->id === (int) $property->id)) {
                $properties->prepend($property);
            }
        }

        $realtors = User::whereHas('roleRelation', fn ($q) => $q->whereIn('kod', ['realtor', 'admin']))
            ->orderBy('familia')->orderBy('imya')->get();
        $clients = User::whereHas('roleRelation', fn ($q) => $q->where('kod', 'client'))
            ->orderBy('familia')->orderBy('imya')->get();

        $propertyItems = $properties->map(fn (Property $p) => ContractFormOptions::propertyItem($p))->values()->all();
        $realtorItems = $realtors->map(fn (User $u) => ContractFormOptions::userItem($u))->values()->all();
        $clientItems = $clients->map(fn (User $u) => ContractFormOptions::userItem($u))->values()->all();

        $defaultOwnerId = old('vladelets_id', $property?->polzovatel_id);

        return view('contracts.create', compact(
            'properties',
            'realtors',
            'clients',
            'property',
            'propertyItems',
            'realtorItems',
            'clientItems',
            'defaultOwnerId'
        ));
    }

    public function searchProperties(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        return response()->json(['items' => ContractFormOptions::searchProperties($q)]);
    }

    public function searchClients(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        return response()->json(['items' => ContractFormOptions::searchClients($q)]);
    }

    public function searchRealtors(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        return response()->json(['items' => ContractFormOptions::searchRealtors($q)]);
    }

    public function store(Request $request): RedirectResponse
    {
        EnsureContractPartiesSchema::apply();

        $user = Auth::user();

        if (!$user->isClient() && !$this->isStaff($user)) {
            abort(403, 'Доступ разрешен только клиентам и риэлторам');
        }

        if ($user->isClient()) {
            return redirect()
                ->route('contracts.index')
                ->withErrors(['error' => 'Клиентам недоступно ручное создание договора. Используйте «Купить онлайн» на странице объявления.']);
        }

        $property = Property::findOrFail($request->input('nedvizhimost_id'));
        $property->load('owners');
        PropertyOwnersService::ensureDefaultOwner($property);
        $operation = $property->operatsiya ?? $property->operation;

        if (!in_array($operation, ['sale', 'rent'], true)) {
            return redirect()->back()->withErrors(['nedvizhimost_id' => 'Недопустимый тип операции объекта']);
        }

        $rules = [
            'nedvizhimost_id' => 'required|exists:nedvizhimost,id',
            'vladelets_id' => 'required|exists:polzovateli,id',
            'pokupatel_id' => 'required|exists:polzovateli,id',
            'tsena' => 'required|numeric|min:0',
            'data_nachala' => 'required|date',
            'primechaniya' => 'nullable|string',
        ];

        if ($operation === 'rent') {
            $rules['data_okonchaniya'] = 'required|date|after_or_equal:data_nachala';
        } else {
            $rules['data_okonchaniya'] = 'nullable|date';
        }

        if ($user->isClient()) {
            $rules['rieltor_id'] = 'required|exists:polzovateli,id';
            $rules['client_party'] = 'required|in:owner,buyer';
        } else {
            $rules['rieltor_id'] = 'required|exists:polzovateli,id';
        }

        $validated = $request->validate($rules);

        if (!empty($validated['primechaniya'])) {
            $noteErrors = TextCensor::fieldError('primechaniya', $validated['primechaniya']);
            if ($noteErrors !== []) {
                return redirect()->back()->withErrors($noteErrors)->withInput();
            }
        }

        $realtor = User::findOrFail($validated['rieltor_id']);
        if (!$realtor->isRealtor() && !$realtor->isAdmin()) {
            return redirect()->back()->withErrors(['rieltor_id' => 'Выбранный пользователь не является риэлтором']);
        }

        $vladeletsId = (int) $validated['vladelets_id'];
        $pokupatelId = (int) $validated['pokupatel_id'];
        $realtorId = (int) $validated['rieltor_id'];

        if (PropertyOwnersService::buyerAmongOwners($property, $pokupatelId)) {
            return redirect()->back()->withErrors([
                'pokupatel_id' => 'Покупатель не может быть собственником этого объекта.',
            ])->withInput();
        }

        if ($vladeletsId === $pokupatelId) {
            return redirect()->back()->withErrors([
                'pokupatel_id' => 'Покупатель и основной продавец должны быть разными лицами.',
            ])->withInput();
        }

        $ownerIds = PropertyOwnersService::ownerUserIds($property);
        if ($ownerIds->isNotEmpty() && !$ownerIds->contains($vladeletsId)) {
            return redirect()->back()->withErrors([
                'vladelets_id' => 'Укажите основного собственника из списка владельцев объекта.',
            ])->withInput();
        }

        if ($user->isClient()) {
            if ($validated['client_party'] === 'owner' && $vladeletsId !== (int) $user->id) {
                return redirect()->back()->withErrors(['vladelets_id' => 'Если вы владелец, укажите себя в поле «Владелец объекта»']);
            }
            if ($validated['client_party'] === 'buyer' && $pokupatelId !== (int) $user->id) {
                return redirect()->back()->withErrors(['pokupatel_id' => 'Если вы покупатель, укажите себя в поле «Покупатель»']);
            }
            $sozdalKak = 'client';
            $sozdalStorona = $validated['client_party'];
        } else {
            if ($realtorId !== (int) $user->id && !$user->isAdmin()) {
                return redirect()->back()->withErrors(['rieltor_id' => 'Укажите себя как риэлтора сделки']);
            }
            $sozdalKak = 'realtor';
            $sozdalStorona = null;
        }

        $pendingStatus = ContractStatus::firstOrCreate(
            ['kod' => 'pending'],
            ['nazvanie' => 'На подтверждении']
        );

        $contract = Contract::create([
            'nedvizhimost_id' => $validated['nedvizhimost_id'],
            'vladelets_id' => $vladeletsId,
            'pokupatel_id' => $pokupatelId,
            'rieltor_id' => $realtorId,
            'sozdal_kak' => $sozdalKak,
            'sozdal_storona' => $sozdalStorona ?? null,
            'tip' => $operation,
            'tsena' => $validated['tsena'],
            'data_nachala' => $validated['data_nachala'],
            'data_okonchaniya' => $operation === 'rent' ? $validated['data_okonchaniya'] : null,
            'status_dogovora_id' => $pendingStatus->id,
            'primechaniya' => $validated['primechaniya'] ?? null,
            'ozhidaet_podtverzhdeniya' => null,
        ]);

        AppNotifier::contractCreated($contract);

        if ($sozdalKak === 'realtor') {
            return redirect()->route('contracts.pending')->with(
                'success',
                'Договор создан. Ожидает подтверждения владельца и покупателя.'
            );
        }

        return redirect()->route('contracts.pending')->with(
            'success',
            'Договор создан. Ожидает подтверждения риэлтора и второй стороны сделки.'
        );
    }

    public function show(Contract $contract): View
    {
        $user = Auth::user();
        $this->assertContractViewAccess($user, $contract);

        $contract->load(['property', 'owner', 'buyer', 'realtor', 'sellers.user']);

        $ecpService = app(ContractEcpService::class);
        $ecpService->autoSignOwnerAndRealtor($contract);
        $contract->refresh();

        $ecpStatuses = $ecpService->signatureStatuses($contract);
        $ecpFullySigned = $ecpService->isFullySigned($contract);
        if ($ecpFullySigned) {
            ContractApproval::finalizeFromEcp($contract);
            $contract->refresh();
        }
        $ecpFullySigned = $ecpService->isFullySigned($contract);
        $displayStatusName = ContractApproval::displayStatusName($contract);
        $canSignEcp = (int) ContractApproval::buyerId($contract) === (int) $user->id
            && !$contract->ecp_podpis_pokupatel_at
            && !in_array($contract->status ?? '', ['cancelled'], true);
        $viewerPartyRole = ContractApproval::partyRoleForUser($contract, $user);

        $statusVersions = \App\Services\ProcessVersionService::history('contract', (int) $contract->id);

        return view('contracts.show', compact(
            'contract',
            'ecpStatuses',
            'canSignEcp',
            'ecpFullySigned',
            'displayStatusName',
            'viewerPartyRole',
            'statusVersions',
        ));
    }

    public function signEcp(Request $request, Contract $contract): RedirectResponse
    {
        $user = Auth::user();
        $this->assertContractViewAccess($user, $contract);

        $request->validate([
            'accept_ecp' => ['accepted'],
        ], [
            'accept_ecp.accepted' => 'Необходимо согласие на подписание УКЭП',
        ]);

        try {
            app(ContractEcpService::class)->signAsBuyer($contract, $user);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('contracts.show', $contract)->withErrors(['error' => $e->getMessage()]);
        }

        $contract->refresh();
        $activated = ContractApproval::finalizeFromEcp($contract);
        $contract = $contract->fresh();

        if ($activated) {
            if (AppNotifier::isOnlineAutoPurchase($contract)) {
                AppNotifier::contractFullyApproved($contract, ContractApproval::buyerId($contract));
                AppNotifier::onlinePurchaseBuyerEcpSigned($contract);
            } else {
                AppNotifier::contractFullyApproved($contract);
            }
        } else {
            AppNotifier::contractEcpSignedByBuyer($contract);
            if (AppNotifier::isOnlineAutoPurchase($contract)) {
                AppNotifier::onlinePurchaseBuyerEcpSigned($contract);
            }
        }

        $message = ($contract->status ?? '') === 'active'
            ? 'Договор подписан УКЭП. Сделка активирована.'
            : 'Договор подписан УКЭП. Подпись отображается в PDF.';

        $redirectRoute = AppNotifier::isOnlineAutoPurchase($contract)
            ? route('purchase.complete', $contract)
            : route('contracts.show', $contract);

        return redirect($redirectRoute)->with('success', $message);
    }

    public function exportPdf(Contract $contract): Response
    {
        $user = Auth::user();
        $this->assertContractViewAccess($user, $contract);

        $contract->load(['property', 'owner', 'buyer', 'client', 'realtor', 'sellers.user']);

        $pdf = Pdf::loadView('contracts.pdf', compact('contract'));
        $filename = 'dogovor_'.$contract->id.'_'.date('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function printRent(Contract $contract): View
    {
        $user = Auth::user();
        $this->assertContractViewAccess($user, $contract);

        if (($contract->tip ?? $contract->type) !== 'rent') {
            abort(404);
        }

        $contract->load(['property.user', 'property', 'owner', 'buyer', 'realtor', 'sellers.user']);

        return view('contracts.print-rent', compact('contract'));
    }

    public function uploadScan(Request $request, Contract $contract): RedirectResponse
    {
        $user = Auth::user();
        $this->assertContractViewAccess($user, $contract);

        if (!$this->canUploadContractScan($user)) {
            abort(403, 'Загружать скан подписанного договора могут только риэлтор или администратор');
        }

        $request->validate([
            'skan_dogovora' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ], [
            'skan_dogovora.required' => 'Выберите файл',
            'skan_dogovora.mimes' => 'Допустимы форматы: PDF, JPG, PNG, WEBP',
            'skan_dogovora.max' => 'Размер файла не более 10 МБ',
        ]);

        if ($contract->skan_dogovora) {
            Storage::disk('public')->delete($contract->skan_dogovora);
        }

        $path = $request->file('skan_dogovora')->store('contract_scans/'.$contract->id, 'public');
        $contract->update(['skan_dogovora' => $path]);

        return redirect()->route('contracts.show', $contract)->with(
            'success',
            'Скан подписанного договора сохранён. Файл доступен всем участникам сделки.'
        );
    }

    public function pending(): View
    {
        $user = Auth::user();

        if (!$user->isClient() && !$this->isStaff($user)) {
            abort(403);
        }

        $pendingStatus = ContractStatus::where('kod', 'pending')->first();

        $query = Contract::query()
            ->when($pendingStatus, fn ($q) => $q->where('status_dogovora_id', $pendingStatus->id))
            ->when(!$pendingStatus, fn ($q) => $q->whereRaw('1 = 0'))
            ->with(['property', 'owner', 'buyer', 'realtor']);

        if ($user->isClient()) {
            $query->where(function ($q) use ($user) {
                $q->where('vladelets_id', $user->id)
                    ->orWhere('pokupatel_id', $user->id)
                    ->orWhere('rieltor_id', $user->id);
            });
        }

        $contracts = $query->latest()->paginate(10)->through(function (Contract $contract) use ($user) {
            $contract->needs_my_approval = ContractApproval::userCanApprove($user, $contract);

            return $contract;
        });

        return view('contracts.pending', compact('contracts'));
    }

    public function approve(Contract $contract): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->isClient() && !$this->isStaff($user)) {
            abort(403);
        }

        if (($contract->status ?? '') !== 'pending') {
            return redirect()->back()->withErrors(['error' => 'Этот договор уже обработан']);
        }

        if (!ContractApproval::userCanApprove($user, $contract)
            && !($user->isAdmin() && $this->isStaff($user))) {
            abort(403, 'Вы уже подтвердили договор или не являетесь стороной сделки');
        }

        ContractApproval::recordApproval($contract, $user);
        $contract->save();

        if (ContractApproval::isFullyApproved($contract)) {
            ContractApproval::activateContract($contract);
            AppNotifier::contractFullyApproved($contract->fresh());

            return redirect()->route('contracts.pending')->with('success', 'Все стороны подтвердили договор. Сделка активирована.');
        }

        $contract->refresh();

        return redirect()->route('contracts.pending')->with(
            'success',
            'Ваше подтверждение учтено. Ожидаем: '.ContractApproval::pendingSummary($contract)
        );
    }

    public function reject(Contract $contract): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->isClient() && !$this->isStaff($user)) {
            abort(403);
        }

        if (($contract->status ?? '') !== 'pending') {
            return redirect()->back()->withErrors(['error' => 'Этот договор уже обработан']);
        }

        if (!ContractApproval::userIsParty($contract, $user) && !$user->isAdmin()) {
            abort(403);
        }

        $cancelledStatus = ContractStatus::firstOrCreate(
            ['kod' => 'cancelled'],
            ['nazvanie' => 'Отклонен']
        );
        $contract->update([
            'status_dogovora_id' => $cancelledStatus->id,
            'ozhidaet_podtverzhdeniya' => null,
        ]);

        return redirect()->route('contracts.pending')->with('success', 'Договор отклонён');
    }

    public function complete(Request $request, Contract $contract): RedirectResponse
    {
        $user = Auth::user();

        $allowed = $user->isAdmin()
            || $this->isStaff($user)
            || ContractApproval::userIsParty($contract, $user);
        if (!$allowed) {
            abort(403);
        }

        if (($contract->tip ?? $contract->type) !== 'rent') {
            return redirect()->back()->withErrors(['error' => 'Завершение доступно только для договоров аренды']);
        }

        if (($contract->status ?? '') !== 'active') {
            return redirect()->back()->withErrors(['error' => 'Можно завершить только активный договор']);
        }

        $completedStatus = ContractStatus::firstOrCreate(
            ['kod' => 'completed'],
            ['nazvanie' => 'Завершён']
        );

        $contract->update(['status_dogovora_id' => $completedStatus->id]);

        $property = Property::find($contract->nedvizhimost_id);
        if ($property) {
            $activeStatus = PropertyStatus::firstOrCreate(['kod' => 'active'], ['nazvanie' => 'Активно']);
            $property->update(['status_obyavleniya_id' => $activeStatus->id]);
        }

        return redirect()->route('contracts.show', $contract)->with('success', 'Аренда завершена. Объект снова доступен в каталоге.');
    }
}
