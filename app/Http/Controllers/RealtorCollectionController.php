<?php

namespace App\Http\Controllers;

use App\Models\CollectionProperty;
use App\Models\Favorite;
use App\Models\Property;
use App\Models\PropertyCollection;
use App\Models\PropertyStatus;
use App\Models\RealtorClient;
use App\Models\User;
use App\Services\AppNotifier;
use App\Support\ContractFormOptions;
use App\Support\RealtorScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RealtorCollectionController extends Controller
{
    public function index(): View
    {
        $query = PropertyCollection::query()->with(['client', 'items']);
        RealtorScope::forRealtor($query);

        $collections = $query->latest('sozdano_at')->paginate(12);

        return view('realtor.collections.index', compact('collections'));
    }

    public function create(): View
    {
        $rieltorId = RealtorScope::currentRealtorId();

        $assigned = RealtorClient::query()->with('client');
        RealtorScope::forRealtor($assigned);
        $clientItems = $assigned->get()
            ->filter(fn (RealtorClient $rc) => $rc->client !== null)
            ->map(fn (RealtorClient $rc) => ContractFormOptions::userItem($rc->client))
            ->values()
            ->all();

        $favoritePropertyIds = Favorite::where('polzovatel_id', $rieltorId)
            ->pluck('nedvizhimost_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $properties = ContractFormOptions::activePropertiesQuery()
            ->with('cityRelation')
            ->orderBy('nazvanie')
            ->limit(150)
            ->get(['id', 'nazvanie', 'tsena', 'gorod_id', 'operatsiya']);

        return view('realtor.collections.create', [
            'clientItems' => $clientItems,
            'clientsSearchUrl' => route('realtor.clients.search'),
            'clientsManageUrl' => route('realtor.clients.index'),
            'favoritesCount' => count($favoritePropertyIds),
            'favoritePropertyIds' => $favoritePropertyIds,
            'properties' => $properties,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nazvanie' => 'required|string|max:255',
            'klient_id' => 'nullable|exists:polzovateli,id',
            'kommentariy' => 'nullable|string|max:2000',
            'property_ids' => 'nullable|array',
            'property_ids.*' => 'exists:nedvizhimost,id',
        ]);

        $rieltorId = RealtorScope::currentRealtorId();
        $klientId = isset($validated['klient_id']) ? (int) $validated['klient_id'] : null;
        if ($err = $this->validateAndAssignClient($rieltorId, $klientId)) {
            return $err;
        }

        $propertyIds = array_map('intval', $validated['property_ids'] ?? []);

        $collection = DB::transaction(function () use ($validated, $rieltorId, $propertyIds) {
            $collection = PropertyCollection::create([
                'rieltor_id' => $rieltorId,
                'klient_id' => $validated['klient_id'] ?? null,
                'nazvanie' => $validated['nazvanie'],
                'token' => PropertyCollection::generateToken(),
                'kommentariy' => $validated['kommentariy'] ?? null,
            ]);

            $this->syncProperties($collection, $propertyIds);

            return $collection;
        });

        if ($collection->klient_id) {
            AppNotifier::collectionShared($collection);
        }

        return redirect()->route('realtor.collections.show', $collection)
            ->with('success', 'Подборка создана');
    }

    public function storeFromFavorites(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nazvanie' => 'required|string|max:255',
            'klient_id' => 'nullable|exists:polzovateli,id',
            'kommentariy' => 'nullable|string|max:2000',
        ]);

        $rieltorId = RealtorScope::currentRealtorId();
        $klientId = isset($validated['klient_id']) ? (int) $validated['klient_id'] : null;
        if ($err = $this->validateAndAssignClient($rieltorId, $klientId)) {
            return $err;
        }

        $propertyIds = Favorite::where('polzovatel_id', $rieltorId)->pluck('nedvizhimost_id')->all();

        if ($propertyIds === []) {
            return back()->withErrors(['error' => 'В избранном нет объектов для подборки']);
        }

        $collection = DB::transaction(function () use ($validated, $rieltorId, $propertyIds) {
            $collection = PropertyCollection::create([
                'rieltor_id' => $rieltorId,
                'klient_id' => $validated['klient_id'] ?? null,
                'nazvanie' => $validated['nazvanie'],
                'token' => PropertyCollection::generateToken(),
                'kommentariy' => $validated['kommentariy'] ?? null,
            ]);

            $this->syncProperties($collection, $propertyIds);

            return $collection;
        });

        if ($collection->klient_id) {
            AppNotifier::collectionShared($collection);
        }

        return redirect()->route('realtor.collections.show', $collection)
            ->with('success', 'Подборка создана из избранного');
    }

    public function show(PropertyCollection $collection): View
    {
        RealtorScope::assertRealtorOwns((int) $collection->rieltor_id);

        $collection->load([
            'client',
            'items.property.images',
            'items.property.cityRelation',
        ]);

        $activeId = PropertyStatus::idFor('active');
        $addable = Property::query()
            ->with('cityRelation')
            ->when($activeId, fn ($q) => $q->where('status_obyavleniya_id', $activeId))
            ->whereNotIn('id', $collection->items->pluck('nedvizhimost_id'))
            ->orderBy('nazvanie')
            ->limit(50)
            ->get(['id', 'nazvanie', 'tsena', 'gorod_id', 'operatsiya']);

        return view('realtor.collections.show', compact('collection', 'addable'));
    }

    public function addProperty(Request $request, PropertyCollection $collection): RedirectResponse
    {
        RealtorScope::assertRealtorOwns((int) $collection->rieltor_id);

        $validated = $request->validate([
            'nedvizhimost_id' => 'required|exists:nedvizhimost,id',
        ]);

        $maxOrder = $collection->items()->max('poryadok') ?? 0;

        CollectionProperty::firstOrCreate(
            [
                'podborka_id' => $collection->id,
                'nedvizhimost_id' => $validated['nedvizhimost_id'],
            ],
            ['poryadok' => $maxOrder + 1]
        );

        return back()->with('success', 'Объект добавлен в подборку');
    }

    public function removeProperty(PropertyCollection $collection, Property $property): RedirectResponse
    {
        RealtorScope::assertRealtorOwns((int) $collection->rieltor_id);

        $collection->items()->where('nedvizhimost_id', $property->id)->delete();

        return back()->with('success', 'Объект убран из подборки');
    }

    public function destroy(PropertyCollection $collection): RedirectResponse
    {
        RealtorScope::assertRealtorOwns((int) $collection->rieltor_id);
        $collection->delete();

        return redirect()->route('realtor.collections.index')->with('success', 'Подборка удалена');
    }

    public function pdf(PropertyCollection $collection): Response
    {
        RealtorScope::assertRealtorOwns((int) $collection->rieltor_id);

        $collection->load([
            'client',
            'realtor',
            'items.property.images',
            'items.property.cityRelation',
        ]);

        $pdf = Pdf::loadView('realtor.collections.pdf', compact('collection'))
            ->setPaper('a4', 'portrait');

        $filename = 'podborka-'.$collection->id.'.pdf';

        return $pdf->download($filename);
    }

    private function validateAndAssignClient(int $rieltorId, ?int $klientId): ?RedirectResponse
    {
        if (!$klientId) {
            return null;
        }

        $client = User::find($klientId);
        if (!$client || !$client->isClient()) {
            return back()->withErrors(['klient_id' => 'Укажите пользователя с ролью «Клиент»'])->withInput();
        }

        RealtorClient::firstOrCreate(
            ['rieltor_id' => $rieltorId, 'klient_id' => $klientId],
            ['status' => 'new']
        );

        return null;
    }

    /** @param  list<int|string>  $propertyIds */
    private function syncProperties(PropertyCollection $collection, array $propertyIds): void
    {
        $order = 0;
        foreach (array_unique(array_map('intval', $propertyIds)) as $propertyId) {
            if ($propertyId <= 0) {
                continue;
            }
            CollectionProperty::create([
                'podborka_id' => $collection->id,
                'nedvizhimost_id' => $propertyId,
                'poryadok' => $order++,
            ]);
        }
    }
}
