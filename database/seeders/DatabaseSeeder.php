<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Начальное заполнение базы при команде php artisan db:seed.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Запуск всех сидеров (точка входа для db:seed).
     */
    public function run(): void
    {
        $this->call(DemoDataSeeder::class);
    }
}
