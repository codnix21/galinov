<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Запрос ссылки для восстановления забытого пароля.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Показать форму «Забыли пароль?».
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Отправить на email ссылку для сброса пароля.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Отправляем ссылку на сброс пароля и показываем пользователю результат.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
