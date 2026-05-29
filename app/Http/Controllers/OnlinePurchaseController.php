<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Services\AppNotifier;
use App\Services\ContractEcpService;
use App\Services\RobokassaService;
use App\Services\TestPaymentService;
use App\Support\ContractApproval;
use App\Support\ContractAutoFill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Онлайн-покупка без риэлтора: просмотр → договор → тестовая оплата → документы.
 */
class OnlinePurchaseController extends Controller
{
    public function show(Property $property): View|RedirectResponse
    {
        if (!$this->isPurchasable($property)) {
            return redirect()->route('properties.show', $property)
                ->with('error', 'Объект недоступен для онлайн-покупки.');
        }

        return view('purchase.buy', [
            'property' => $property->load(['user', 'cityRelation', 'images']),
            'mortgageUrl' => route('pages.mortgage-calculator', ['price' => (int) $property->tsena, 'property_id' => $property->id]),
        ]);
    }

    public function store(Request $request, Property $property): RedirectResponse
    {
        if (!$this->isPurchasable($property)) {
            return back()->with('error', 'Объект недоступен для покупки.');
        }

        $user = $request->user();
        if ((int) ($property->polzovatel_id ?? 0) === (int) $user->id) {
            return back()->with('error', 'Нельзя купить собственный объект.');
        }

        $validated = $request->validate([
            'confirm_terms' => ['accepted'],
        ]);

        try {
            $contract = ContractAutoFill::createPendingContract($property, $user, null, 'client');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        AppNotifier::contractCreated($contract);
        AppNotifier::onlinePurchaseContractCreated($contract);

        return redirect()->route('purchase.payment', $contract)
            ->with('success', 'Договор сформирован автоматически. Подтвердите тестовую оплату.');
    }

    public function payment(Contract $contract): View|RedirectResponse
    {
        $this->authorizeBuyer($contract);

        $contract->load(['property', 'owner', 'buyer', 'realtor', 'statusRelation']);

        $robokassa = app(RobokassaService::class);

        return view('purchase.payment', [
            'contract' => $contract,
            'robokassaEnabled' => $robokassa->isConfigured(),
            'testGatewayEnabled' => (bool) config('services.payment.test_gateway', false),
        ]);
    }

    public function payRobokassa(Request $request, Contract $contract, RobokassaService $robokassa): RedirectResponse
    {
        $this->authorizeBuyer($contract);

        $request->validate([
            'accept_offer' => ['accepted'],
        ]);

        if ($contract->isPaid()) {
            return redirect()->route('purchase.complete', $contract);
        }

        if (!$robokassa->isConfigured()) {
            return redirect()->route('purchase.payment', $contract)
                ->with('error', 'Оплата Robokassa не настроена. Проверьте ROBOKASSA_* в .env');
        }

        $amount = (float) ($contract->tsena ?? 0);
        if ($amount <= 0) {
            return redirect()->route('purchase.payment', $contract)
                ->with('error', 'Сумма договора не указана.');
        }

        return redirect()->away($robokassa->paymentUrl($contract));
    }

    public function paySimulate(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeBuyer($contract);

        if ($contract->isPaid()) {
            return redirect()->route('purchase.complete', $contract);
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'in:card,sbp'],
            'card_number' => ['required_if:payment_method,card', 'nullable', 'string', 'max:24'],
            'card_expiry' => ['required_if:payment_method,card', 'nullable', 'string', 'max:7'],
            'card_cvc' => ['required_if:payment_method,card', 'nullable', 'string', 'max:4'],
            'card_holder' => ['nullable', 'string', 'max:120'],
            'sbp_phone' => ['required_if:payment_method,sbp', 'nullable', 'string', 'max:20'],
            'accept_offer' => ['accepted'],
        ]);

        $result = app(TestPaymentService::class)->process(
            $contract,
            $validated['payment_method'],
            $validated,
        );

        if (!$result['ok']) {
            return back()->withInput()->withErrors(['error' => $result['message']]);
        }

        AppNotifier::onlinePurchasePaid($contract->fresh());

        return redirect()->route('purchase.complete', $contract)
            ->with('success', $result['message'] . ' ID: ' . $result['transaction_id']);
    }

    public function complete(Contract $contract): View|RedirectResponse
    {
        $this->authorizeBuyer($contract);

        if (!$contract->isPaid()) {
            return redirect()->route('purchase.payment', $contract)
                ->with('error', 'Сначала завершите оплату.');
        }

        $contract->load(['property', 'owner', 'buyer', 'realtor', 'statusRelation']);

        $ecpService = app(ContractEcpService::class);
        $ecpService->autoSignOwnerAndRealtor($contract);
        $contract->refresh();

        $ecpStatuses = $ecpService->signatureStatuses($contract);
        $ecpFullySigned = $ecpService->isFullySigned($contract);
        $user = auth()->user();
        $canSignEcp = $user
            && (int) ContractApproval::buyerId($contract) === (int) $user->id
            && !$contract->ecp_podpis_pokupatel_at;

        return view('purchase.complete', compact('contract', 'ecpStatuses', 'canSignEcp', 'ecpFullySigned'));
    }

    private function authorizeBuyer(Contract $contract): void
    {
        $user = request()->user();
        $buyerId = (int) ($contract->pokupatel_id ?? 0);

        if ((int) $user->id !== $buyerId && !$user->isAdmin()) {
            abort(403);
        }
    }

    private function isPurchasable(Property $property): bool
    {
        $activeId = PropertyStatus::idFor('active');
        if ($activeId === null || (int) $property->status_obyavleniya_id !== (int) $activeId) {
            return false;
        }

        return in_array($property->operatsiya ?? '', ['sale', 'rent'], true);
    }
}
