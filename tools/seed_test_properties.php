<?php

/**
 * Создаёт 40 случайных активных объявлений для пользователя t@mail.ru (нагрузка / демо).
 * Запуск: php tools/seed_test_properties.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\City;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\User;

$email = 't@mail.ru';
$user = User::where('email_polzovatela', $email)->first();

if (!$user) {
    echo "user_not_found\n";
    exit(1);
}

$city = City::firstOrCreate(['nazvanie' => 'Москва']);
$activeStatus = PropertyStatus::where('kod', 'active')->firstOrFail();

$types = ['apartment', 'house', 'commercial', 'land'];
$count = 40;
$created = 0;

for ($i = 1; $i <= $count; $i++) {
    $type = $types[array_rand($types)];
    $data = [
        'nazvanie' => "Тестовое объявление $i",
        'opisanie' => "Описание тестового объявления $i",
        'tip' => $type,
        'operatsiya' => 'sale',
        'tsena' => rand(1_500_000, 15_000_000),
        'gorod_id' => $city->id,
        'adres_ulitsy' => 'ул. Тестовая, д.' . rand(1, 300),
        'ploshchad' => rand(25, 180),
        'komnaty' => rand(1, 6),
        'etazh' => rand(1, 25),
        'vsego_etazhey' => rand(5, 30),
        'polzovatel_id' => $user->id,
        'status_obyavleniya_id' => $activeStatus->id,
    ];

    Property::create($data);
    $created++;
}

echo "created=$created\n";
