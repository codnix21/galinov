<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\RentPayment;
use App\Services\RentScheduleService;
use App\Support\ContractApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RentPaymentController extends Controller
{
    public function generate(Contract $contract): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isStaff()) {
            abort(403);
        }

        $n = RentScheduleService::generateForContract($contract);

        return back()->with('success', "График аренды: создано платежей — {$n}.");
    }

    public function markPaid(RentPayment $payment): RedirectResponse
    {
        $user = Auth::user();
        $contract = $payment->contract;
        if (!$user->isStaff() && !ContractApproval::userIsParty($contract, $user)) {
            abort(403);
        }

        RentScheduleService::markPaid($payment);

        return back()->with('success', 'Платёж отмечен оплаченным.');
    }
}
