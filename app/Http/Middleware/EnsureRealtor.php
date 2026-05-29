<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Доступ к разделу обучения — только риэлтор (и администратор для проверки материалов).
 */
class EnsureRealtor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (!$user || (!$user->isRealtor() && !$user->isAdmin())) {
            abort(403, 'Раздел обучения доступен риэлторам');
        }

        return $next($request);
    }
}
