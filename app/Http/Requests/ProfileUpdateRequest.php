<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Проверка данных при сохранении профиля пользователя.
 */
class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('pol') === '') {
            $this->merge(['pol' => null]);
        }
    }

    /**
     * Правила для имени, email, биографии и аватара.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'familia' => ['required', 'string', 'max:255'],
            'imya' => ['required', 'string', 'max:255'],
            'otchestvo' => ['nullable', 'string', 'max:255'],
            'email_polzovatela' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('polzovateli', 'email_polzovatela')->ignore($this->user()->id),
            ],
            'telefon' => ['nullable', 'string', 'max:32', 'regex:/^[\d\s\-\+\(\)]+$/u'],
            'pol' => ['nullable', 'string', Rule::in(['male', 'female'])],
            'biografiya' => ['nullable', 'string', 'max:1000'],
            'avatar_polzovatela' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'familia.required' => 'Укажите фамилию.',
            'imya.required' => 'Укажите имя.',
            'email_polzovatela.required' => 'Укажите email.',
            'email_polzovatela.email' => 'Введите корректный email.',
            'email_polzovatela.unique' => 'Этот email уже занят.',
            'telefon.regex' => 'Телефон: только цифры, пробелы и символы + - ( ).',
            'pol.in' => 'Выберите пол из списка.',
            'avatar_polzovatela.image' => 'Аватар должен быть изображением.',
            'avatar_polzovatela.max' => 'Размер аватара не более 2 МБ.',
        ];
    }
}
