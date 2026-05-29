<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Services\AppNotifier;
use App\Services\RobokassaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Callback-и Robokassa: Result (сервер), Success и Fail (браузер пользователя).
 */
class RobokassaPaymentController extends Controller
{
    public function result(Request $request, RobokassaService $robokassa): Response
    {
        $outSum = (string) $request->input('OutSum', '');
        $invId = (int) $request->input('InvId', 0);
        $signature = (string) $request->input('SignatureValue', '');

        if ($invId <= 0 || $outSum === '' || $signature === '') {
            Log::warning('Robokassa result: missing parameters', $request->all());

            return response('bad request', 400);
        }

        if (!$robokassa->verifyResultSignature($outSum, $invId, $signature, $robokassa->extractShpParams($request->all()))) {
            Log::warning('Robokassa result: invalid signature', ['InvId' => $invId]);

            return response('bad signature', 403);
        }

        $contract = Contract::query()->find($invId);
        if (!$contract) {
            return response('invoice not found', 404);
        }

        $expectedSum = $robokassa->formatAmount((float) ($contract->tsena ?? 0));
        if (!hash_equals($expectedSum, $robokassa->formatAmount((float) $outSum))) {
            Log::warning('Robokassa result: amount mismatch', [
                'InvId' => $invId,
                'expected' => $expectedSum,
                'got' => $outSum,
            ]);

            return response('bad amount', 400);
        }

        if (!$contract->isPaid()) {
            $contract->update([
                'oplata_status' => 'robokassa_paid',
                'oplata_at' => now(),
                'oplata_metod' => 'robokassa',
                'oplata_tranzaktsiya' => 'RK-' . $invId . '-' . now()->timestamp,
                'oplata_summa' => (float) $outSum,
            ]);
            AppNotifier::onlinePurchasePaid($contract->fresh());
        }

        return response('OK' . $invId);
    }

    public function success(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $invId = (int) $request->input('InvId', 0);
        $contract = $invId > 0 ? Contract::query()->find($invId) : null;

        if (!$contract) {
            return redirect()->route('properties.index')
                ->with('error', 'Договор для оплаты не найден.');
        }

        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('error', 'Войдите в аккаунт, чтобы открыть квитанцию по оплате.');
        }

        if (!$this->userCanViewContract($contract)) {
            abort(403);
        }

        if ($contract->isPaid()) {
            return redirect()->route('purchase.complete', $contract)
                ->with('success', 'Оплата прошла успешно.');
        }

        return redirect()->route('purchase.payment', $contract)
            ->with('error', 'Платёж ещё обрабатывается. Обновите страницу через минуту.');
    }

    public function fail(Request $request): \Illuminate\Http\RedirectResponse
    {
        $invId = (int) $request->input('InvId', 0);
        $contract = $invId > 0 ? Contract::query()->find($invId) : null;

        if ($contract && auth()->check() && $this->userCanViewContract($contract)) {
            return redirect()->route('purchase.payment', $contract)
                ->with('error', 'Оплата отменена или не завершена.');
        }

        return redirect()->route('properties.index')
            ->with('error', 'Оплата отменена.');
    }

    private function userCanViewContract(Contract $contract): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $buyerId = (int) ($contract->pokupatel_id ?? 0);

        return (int) $user->id === $buyerId;
    }
}
