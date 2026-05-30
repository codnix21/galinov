<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminHealthController extends Controller
{
    public function index(): View
    {
        if (!Auth::user()?->canAccessAdminPanel()) {
            abort(403);
        }

        return view('admin.health', [
            'checks' => SystemHealthService::checks(),
        ]);
    }
}
