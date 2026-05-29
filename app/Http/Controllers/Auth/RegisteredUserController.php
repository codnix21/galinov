<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Регистрация новых пользователей на сайте.
 */
class RegisteredUserController extends Controller
{
    /**
     * Показать страницу регистрации.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Обработать отправку формы регистрации и войти под новым пользователем.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'familia' => ['required', 'string', 'max:255'],
            'imya' => ['required', 'string', 'max:255'],
            'otchestvo' => ['nullable', 'string', 'max:255'],
            'email_polzovatela' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:polzovateli,email_polzovatela'],
            'parol' => ['required', 'confirmed', Rules\Password::defaults()],
            'telefon' => ['nullable', 'string', 'max:20'],
        ], [
            'familia.required' => 'Поле "Фамилия" обязательно для заполнения.',
            'familia.max' => 'Фамилия не должна превышать 255 символов.',
            'imya.required' => 'Поле "Имя" обязательно для заполнения.',
            'imya.max' => 'Имя не должно превышать 255 символов.',
            'otchestvo.max' => 'Отчество не должно превышать 255 символов.',
            'email_polzovatela.required' => 'Поле "Email" обязательно для заполнения.',
            'email_polzovatela.email' => 'Введите корректный email адрес.',
            'email_polzovatela.unique' => 'Этот email уже зарегистрирован.',
            'email_polzovatela.max' => 'Email не должен превышать 255 символов.',
            'parol.required' => 'Поле "Пароль" обязательно для заполнения.',
            'parol.confirmed' => 'Пароли не совпадают.',
        ]);

        $role = Role::firstOrCreate(
            ['kod' => 'client'],
            ['nazvanie' => 'Клиент']
        );

        $user = User::create([
            'familia' => $request->familia,
            'imya' => $request->imya,
            'otchestvo' => $request->otchestvo,
            'email_polzovatela' => $request->email_polzovatela,
            'parol' => Hash::make($request->parol),
            'telefon' => $request->telefon,
            'rol_id' => $role->id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('cabinet.index'));
    }
}
