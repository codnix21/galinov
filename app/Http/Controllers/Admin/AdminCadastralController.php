<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Support\CadastralDuplicateChecker;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminCadastralController extends Controller
{
    public function index(): View
    {
        if (!Auth::user()->canAccessAdminPanel()) {
            abort(403);
        }

        $activeId = PropertyStatus::idFor('active');
        $pendingId = PropertyStatus::idFor('pending_review');

        $groups = Property::query()
            ->whereNotNull('kadastrovy_nomer')
            ->where('kadastrovy_nomer', '!=', '')
            ->when($activeId || $pendingId, function ($q) use ($activeId, $pendingId) {
                $q->where(function ($w) use ($activeId, $pendingId) {
                    if ($activeId) {
                        $w->orWhere('status_obyavleniya_id', $activeId);
                    }
                    if ($pendingId) {
                        $w->orWhere('status_obyavleniya_id', $pendingId);
                    }
                });
            })
            ->orderBy('kadastrovy_nomer')
            ->get()
            ->groupBy(fn (Property $p) => CadastralDuplicateChecker::normalize($p->kadastrovy_nomer))
            ->filter(fn ($items, $key) => $key && $items->count() > 1);

        return view('admin.cadastral-duplicates', ['groups' => $groups]);
    }
}
