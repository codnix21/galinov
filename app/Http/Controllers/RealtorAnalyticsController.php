<?php

namespace App\Http\Controllers;

use App\Services\RealtorAnalyticsService;
use App\Support\RealtorScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RealtorAnalyticsController extends Controller
{
    public function index(): View
    {
        if (!Auth::user()->isStaff()) {
            abort(403);
        }

        $allStaff = Auth::user()->isAdmin();
        $realtorId = RealtorScope::currentRealtorId();
        $stats = RealtorAnalyticsService::dashboard($realtorId, $allStaff);

        return view('realtor.analytics', compact('stats', 'allStaff'));
    }
}
