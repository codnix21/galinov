<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractReview;
use App\Models\ContractStatus;
use App\Support\ContractApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractReviewController extends Controller
{
    public function store(Request $request, Contract $contract): RedirectResponse
    {
        $user = Auth::user();
        if (!ContractApproval::userIsParty($contract, $user)) {
            abort(403);
        }

        $activeId = ContractStatus::idFor('active');
        if ($activeId === null || (int) $contract->status_dogovora_id !== (int) $activeId) {
            return back()->with('error', 'Отзыв можно оставить только по завершённой активной сделке.');
        }

        $validated = $request->validate([
            'ocenka' => 'required|integer|min:1|max:5',
            'tekst' => 'nullable|string|max:2000',
        ]);

        ContractReview::updateOrCreate(
            ['dogovor_id' => $contract->id, 'polzovatel_id' => $user->id],
            ['ocenka' => $validated['ocenka'], 'tekst' => $validated['tekst'] ?? null],
        );

        return back()->with('success', 'Спасибо за отзыв!');
    }
}
