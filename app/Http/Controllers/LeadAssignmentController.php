<?php

namespace App\Http\Controllers;

use App\Models\PropertyInquiry;
use App\Models\PropertySelectionRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadAssignmentController extends Controller
{
    public function assignInquiry(Request $request, PropertyInquiry $inquiry): RedirectResponse
    {
        if (!Auth::user()->isStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'naznachen_rieltor_id' => 'nullable|exists:polzovateli,id',
        ]);

        $realtorId = $validated['naznachen_rieltor_id'] ?? null;
        if ($realtorId) {
            $r = User::find($realtorId);
            if (!$r || (!$r->isRealtor() && !$r->isAdmin())) {
                return back()->withErrors(['naznachen_rieltor_id' => 'Выберите риэлтора.']);
            }
        }

        $inquiry->update(['naznachen_rieltor_id' => $realtorId]);

        return back()->with('success', 'Лид назначен.');
    }

    public function assignSelection(Request $request, PropertySelectionRequest $selectionRequest): RedirectResponse
    {
        if (!Auth::user()->isStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'naznachen_rieltor_id' => 'nullable|exists:polzovateli,id',
        ]);

        $realtorId = $validated['naznachen_rieltor_id'] ?? null;
        if ($realtorId) {
            $r = User::find($realtorId);
            if (!$r || (!$r->isRealtor() && !$r->isAdmin())) {
                return back()->withErrors(['naznachen_rieltor_id' => 'Выберите риэлтора.']);
            }
        }

        $selectionRequest->update(['naznachen_rieltor_id' => $realtorId]);

        return back()->with('success', 'Лид назначен.');
    }
}
