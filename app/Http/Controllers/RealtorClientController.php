<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\RealtorClient;
use App\Models\User;
use App\Services\AppNotifier;
use App\Support\ContractFormOptions;
use App\Support\RealtorCrm;
use App\Support\RealtorScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RealtorClientController extends Controller
{
    public function index(Request $request): View
    {
        $query = RealtorClient::query()
            ->with(['client', 'realtor']);
        RealtorScope::forRealtor($query);

        if ($request->filled('status')) {
            $query->whereStatusKod($request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->whereHas('client', function ($q) use ($search) {
                ContractFormOptions::applyFioSearch($q, $search);
            });
        }

        $clients = $query->latest('obnovleno_at')->paginate(15)->withQueryString();

        $assignedQ = RealtorClient::query();
        RealtorScope::forRealtor($assignedQ);
        $assignedIds = $assignedQ->pluck('klient_id');
        $clientRoleId = \App\Models\Role::where('kod', 'client')->value('id');
        $availableClients = User::query()
            ->when($clientRoleId, fn ($q) => $q->where('rol_id', $clientRoleId))
            ->when($assignedIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $assignedIds))
            ->orderBy('familia')
            ->limit(100)
            ->get();

        return view('realtor.clients.index', [
            'clients' => $clients,
            'statusOptions' => RealtorCrm::clientStatuses(),
            'availableClients' => $availableClients,
        ]);
    }

    public function show(RealtorClient $realtorClient): View
    {
        RealtorScope::assertRealtorOwns((int) $realtorClient->rieltor_id);

        $realtorClient->load(['client', 'realtor']);

        $clientId = (int) $realtorClient->klient_id;

        $contracts = Contract::query()
            ->where(function ($q) use ($clientId) {
                $q->where('vladelets_id', $clientId)
                    ->orWhere('pokupatel_id', $clientId);
            })
            ->with(['property', 'realtor'])
            ->latest()
            ->limit(10)
            ->get();

        $tasks = \App\Models\RealtorTask::query()
            ->where('rieltor_id', $realtorClient->rieltor_id)
            ->where('klient_id', $clientId)
            ->latest('srok_do')
            ->limit(10)
            ->get();

        $showings = \App\Models\PropertyShowing::query()
            ->where('rieltor_id', $realtorClient->rieltor_id)
            ->where('klient_id', $clientId)
            ->with('property')
            ->orderByDesc('naznacheno_na')
            ->limit(10)
            ->get();

        $activeStatusId = PropertyStatus::idFor('active');
        $propertyOptions = Property::query()
            ->when($activeStatusId, fn ($q) => $q->where('status_obyavleniya_id', $activeStatusId))
            ->orderBy('nazvanie')
            ->limit(200)
            ->get(['id', 'nazvanie', 'adres_ulitsy']);

        return view('realtor.clients.show', [
            'assignment' => $realtorClient,
            'contracts' => $contracts,
            'tasks' => $tasks,
            'showings' => $showings,
            'propertyOptions' => $propertyOptions,
            'statusOptions' => RealtorCrm::clientStatuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'klient_id' => 'required|exists:polzovateli,id',
            'status' => 'nullable|in:new,in_progress,deal,lost',
            'zametki' => 'nullable|string|max:5000',
        ]);

        $client = User::findOrFail($validated['klient_id']);
        if (!$client->isClient()) {
            return back()->withErrors(['klient_id' => 'Можно закрепить только пользователя с ролью «Клиент»']);
        }

        $rieltorId = RealtorScope::currentRealtorId();

        if (RealtorClient::where('rieltor_id', $rieltorId)->where('klient_id', $client->id)->exists()) {
            return back()->withErrors(['klient_id' => 'Клиент уже закреплён за вами']);
        }

        $assignment = RealtorClient::create([
            'rieltor_id' => $rieltorId,
            'klient_id' => $client->id,
            'status' => $validated['status'] ?? 'new',
            'zametki' => $validated['zametki'] ?? null,
        ]);

        AppNotifier::clientAssigned($client, $assignment);

        return redirect()->route('realtor.clients.show', $assignment)
            ->with('success', 'Клиент закреплён');
    }

    public function update(Request $request, RealtorClient $realtorClient): RedirectResponse
    {
        RealtorScope::assertRealtorOwns((int) $realtorClient->rieltor_id);

        $validated = $request->validate([
            'status' => 'required|in:new,in_progress,deal,lost',
            'zametki' => 'nullable|string|max:5000',
        ]);

        $realtorClient->update($validated);

        return back()->with('success', 'Карточка клиента обновлена');
    }

    public function destroy(RealtorClient $realtorClient): RedirectResponse
    {
        RealtorScope::assertRealtorOwns((int) $realtorClient->rieltor_id);
        $realtorClient->delete();

        return redirect()->route('realtor.clients.index')->with('success', 'Клиент снят с закрепления');
    }

    public function searchClients(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        return response()->json(['items' => ContractFormOptions::searchClients($q)]);
    }
}
