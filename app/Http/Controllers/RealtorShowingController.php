<?php

namespace App\Http\Controllers;

use App\Models\PropertyShowing;
use App\Models\RealtorClient;
use App\Services\AppNotifier;
use App\Support\RealtorCrm;
use App\Support\RealtorScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class RealtorShowingController extends Controller
{
    public function index(Request $request): View
    {
        $query = PropertyShowing::query()->with(['client', 'property']);
        RealtorScope::forRealtor($query);

        if ($request->string('filter')->toString() === 'past') {
            $query->where('naznacheno_na', '<', now());
        } else {
            $query->where('naznacheno_na', '>=', now()->startOfDay());
        }

        $showings = $query->orderBy('naznacheno_na')->paginate(15)->withQueryString();

        $clients = RealtorClient::query()->with('client');
        RealtorScope::forRealtor($clients);

        return view('realtor.showings.index', [
            'showings' => $showings,
            'clientOptions' => $clients->get(),
            'resultOptions' => RealtorCrm::showingResults(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'klient_id' => 'required|exists:polzovateli,id',
            'nedvizhimost_id' => 'required|exists:nedvizhimost,id',
            'naznacheno_na' => 'required|date',
            'zametki' => 'nullable|string|max:2000',
        ]);

        $rieltorId = RealtorScope::currentRealtorId();

        $assigned = RealtorClient::where('rieltor_id', $rieltorId)
            ->where('klient_id', $validated['klient_id'])
            ->exists();
        if (!$assigned && !RealtorScope::isAgencyView()) {
            return back()->withErrors(['klient_id' => 'Сначала закрепите клиента в CRM']);
        }

        $showing = PropertyShowing::create([
            'rieltor_id' => $rieltorId,
            'klient_id' => $validated['klient_id'],
            'nedvizhimost_id' => $validated['nedvizhimost_id'],
            'naznacheno_na' => Carbon::parse($validated['naznacheno_na']),
            'zametki' => $validated['zametki'] ?? null,
        ]);

        AppNotifier::showingScheduled($showing);

        return back()->with('success', 'Показ запланирован');
    }

    public function update(Request $request, PropertyShowing $showing): RedirectResponse
    {
        RealtorScope::assertRealtorOwns((int) $showing->rieltor_id);

        $validated = $request->validate([
            'rezultat' => 'nullable|in:interested,not_interested,no_show,deal',
            'zametki' => 'nullable|string|max:2000',
            'naznacheno_na' => 'nullable|date',
        ]);

        $showing->update($validated);

        return back()->with('success', 'Показ обновлён');
    }

    public function destroy(PropertyShowing $showing): RedirectResponse
    {
        RealtorScope::assertRealtorOwns((int) $showing->rieltor_id);
        $showing->delete();

        return back()->with('success', 'Показ удалён');
    }
}
