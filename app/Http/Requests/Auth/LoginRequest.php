<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Запрос на вход: проверка полей, лимит попыток и аутентификация.
 */
class LoginRequest extends FormRequest
{
    /**
     * Разрешить обработку запроса (вход доступен всем гостям).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила проверки email и пароля из формы входа.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Принимаем email из формы, но маппим его на email_polzovatela
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Попытаться войти по введённым данным; учесть блокировку аккаунта.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Используем правильные названия полей из базы данных:
        // - идентификатор: email_polzovatela (см. getAuthIdentifierName в модели User)
        // - пароль: ключ "password" 
        $credentials = [
            'email_polzovatela' => (string) $this->input('email'),
            'password' => (string) $this->input('password'),
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Проверяем, не заблокирован ли пользователь
        $user = Auth::user();
        if ($user && $user->isBlocked()) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Ваш аккаунт заблокирован. Обратитесь к администратору.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Не допустить слишком много неудачных попыток входа подряд.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Ключ для счётчика ограничения попыток (email + IP).
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('email')).'|'.$this->ip());
    }
}
