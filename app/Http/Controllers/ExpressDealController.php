<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\RealtorClient;
use App\Models\Role;
use App\Models\User;
use App\Services\AppNotifier;
use App\Support\ContractAutoFill;
use App\Support\ContractFormOptions;
use App\Support\PropertyListingAuthor;
use App\Support\RealtorScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Экспресс-сделка: из карточки объекта — только данные покупателя, договор заполняется сам.
 */
class ExpressDealController extends Controller
{
    public function show(Request $request, Property $property): View|RedirectResponse
    {
        if (!$this->canManageDeal($request, $property)) {
            abort(403);
        }

        $property->load(['user', 'cityRelation']);

        $user = $request->user();
        $clientItems = [];
        if ($user->isRealtor() || $user->isAdmin()) {
            $assigned = RealtorClient::query()->with('client');
            if ($user->isRealtor()) {
                RealtorScope::forRealtor($assigned);
            }
            $clientItems = $assigned->get()
                ->filter(fn (RealtorClient $rc) => $rc->client !== null)
                ->map(fn (RealtorClient $rc) => ContractFormOptions::userItem($rc->client))
                ->values()
                ->all();
        }

        return view('deals.express', [
            'property' => $property,
            'clientItems' => $clientItems,
            'clientsSearchUrl' => route('api.contracts.search.clients'),
        ]);
    }

    public function store(Request $request, Property $property): RedirectResponse
    {
        if (!$this->canManageDeal($request, $property)) {
            abort(403);
        }

        $validated = $request->validate([
            'buyer_id' => ['nullable', 'integer', 'exists:polzovateli,id'],
            'buyer_email' => ['nullable', 'email', 'max:255'],
            'buyer_familia' => ['nullable', 'string', 'max:100'],
            'buyer_imya' => ['nullable', 'string', 'max:100'],
            'buyer_otchestvo' => ['nullable', 'string', 'max:100'],
        ]);

        $buyer = null;
        if (!empty($validated['buyer_id'])) {
            $buyer = User::find($validated['buyer_id']);
            if ($buyer && !$buyer->isClient()) {
                return back()->withInput()->withErrors(['buyer_id' => 'Покупатель должен быть пользователем с ролью «Клиент».']);
            }
            if ($buyer && $request->user()->isRealtor()) {
                RealtorClient::firstOrCreate(
                    ['rieltor_id' => (int) $request->user()->id, 'klient_id' => $buyer->id],
                    ['status' => 'new']
                );
            }
        } elseif (!empty($validated['buyer_email'])) {
            $buyer = User::where('email_polzovatela', $validated['buyer_email'])->first();
            if (!$buyer && !empty($validated['buyer_imya'])) {
                $clientRole = Role::where('kod', 'client')->first();
                $buyer = User::create([
                    'email_polzovatela' => $validated['buyer_email'],
                    'familia' => $validated['buyer_familia'] ?? 'Клиент',
                    'imya' => $validated['buyer_imya'] ?? '',
                    'otchestvo' => $validated['buyer_otchestvo'] ?? null,
                    'parol' => str()->random(16),
                    'rol_id' => $clientRole?->id,
                ]);
            }
        }

        if (!$buyer) {
            return back()->withInput()->with('error', 'Укажите существующего покупателя или email с именем для нового клиента.');
        }

        $realtor = $request->user()->isRealtor() || $request->user()->isAdmin()
            ? $request->user()
            : ContractAutoFill::defaultRealtor();

        try {
            $contract = ContractAutoFill::createPendingContract(
                $property,
                $buyer,
                $realtor,
                $request->user()->isStaff() ? 'realtor' : 'owner',
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AppNotifier::contractCreated($contract);

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Договор создан автоматически по данным объекта. Осталось подтвердить сторонам.');
    }

    private function canManageDeal(Request $request, Property $property): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        return PropertyListingAuthor::canManage($user, $property);
    }
}
