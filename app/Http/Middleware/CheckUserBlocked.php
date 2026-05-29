<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Если аккаунт заблокирован — выход из системы и редирект на вход с сообщением.
 */
class CheckUserBlocked
{
    /**
     * Проверяет блокировку у авторизованного пользователя перед обработкой запроса.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isBlocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Ваш аккаунт заблокирован. Обратитесь к администратору.',
            ]);
        }

        return $next($request);
    }
}


