<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Доступ только администратору или риэлтору (модерация объявлений и служебные разделы).
 */
class CanModerateProperties
{
    /**
     * Пропускает запрос дальше, если пользователь — сотрудник; иначе 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isRealtor())) {
            abort(403, 'Доступ только для сотрудников');
        }

        return $next($request);
    }
}
