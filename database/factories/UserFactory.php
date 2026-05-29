<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Фабрика для генерации тестовых пользователей в тестах и сидерах.
 * Пароль по умолчанию — «password».
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $role = Role::where('kod', 'client')->first();

        return [
            'familia' => fake()->lastName(),
            'imya' => fake()->firstName(),
            'otchestvo' => fake()->optional(0.6)->firstName() . 'ович',
            'email_polzovatela' => fake()->unique()->safeEmail(),
            'parol' => static::$password ??= Hash::make('password'),
            'rol_id' => $role?->id ?? 3,
            'telefon' => '+7 (9' . fake()->numerify('##') . ') ' . fake()->numerify('###-##-##'),
            'remember_token' => Str::random(10),
            'zablokirovan' => false,
        ];
    }

    public function realtor(): static
    {
        return $this->state(function () {
            $role = Role::where('kod', 'realtor')->first();

            return ['rol_id' => $role?->id ?? 2];
        });
    }

    public function client(): static
    {
        return $this->state(function () {
            $role = Role::where('kod', 'client')->first();

            return ['rol_id' => $role?->id ?? 3];
        });
    }

    public function admin(): static
    {
        return $this->state(function () {
            $role = Role::where('kod', 'admin')->first();

            return ['rol_id' => $role?->id ?? 1];
        });
    }
}
