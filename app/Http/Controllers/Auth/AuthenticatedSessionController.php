<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Вход в систему и выход из аккаунта.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Показать страницу входа.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Проверить логин и пароль, создать сессию и перенаправить пользователя.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Перенаправляем админа в админ панель, остальных в личный кабинет
        if (Auth::user()->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }
        
        return redirect()->intended(route('cabinet.index'));
    }

    /**
     * Выйти из аккаунта и завершить сессию.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
