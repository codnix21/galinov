<?php

/**
 * Тестовые данные: риэлтор, клиент, объявление и договор в статусе «ожидает подтверждения».
 * Запуск: php tools/seed_pending_contract.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\City;
use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$realtorEmail = 't@mail.ru';
$clientEmail  = 'roma@mail.ru';

$realtorRole = Role::where('kod', 'realtor')->firstOrFail();
$clientRole = Role::where('kod', 'client')->firstOrFail();

// Получаем или создаём риэлтора
$realtor = User::firstOrCreate(
    ['email_polzovatela' => $realtorEmail],
    [
        'familia' => 'Риэлтор',
        'imya' => 'Тест',
        'otchestvo' => null,
        'parol' => Hash::make('Password123!'),
        'rol_id' => $realtorRole->id,
    ]
);

// Убедимся, что роль риэлтор
if ($realtor->role !== 'realtor') {
    $realtor->rol_id = $realtorRole->id;
    $realtor->save();
}

// Получаем или создаём клиента
$client = User::firstOrCreate(
    ['email_polzovatela' => $clientEmail],
    [
        'familia' => 'Клиент',
        'imya' => 'Тест',
        'otchestvo' => null,
        'parol' => Hash::make('Password123!'),
        'rol_id' => $clientRole->id,
    ]
);

$city = City::firstOrCreate(['nazvanie' => 'Москва']);
$activeStatus = PropertyStatus::where('kod', 'active')->firstOrFail();
$pendingContractStatus = ContractStatus::where('kod', 'pending')->firstOrFail();

// Создаём тестовый объект недвижимости для риэлтора
$property = Property::create([
    'nazvanie' => 'Тестовый объект для договора',
    'opisanie' => 'Автотест pending',
    'tip' => 'apartment',
    'operatsiya' => 'sale',
    'tsena' => 5_000_000,
    'gorod_id' => $city->id,
    'adres_ulitsy' => 'ул. Демонстрационная, д.1',
    'ploshchad' => 50,
    'komnaty' => 2,
    'etazh' => 3,
    'vsego_etazhey' => 10,
    'polzovatel_id' => $realtor->id,
    'status_obyavleniya_id' => $activeStatus->id,
]);

// Создаём договор в статусе pending
Contract::create([
    'nedvizhimost_id' => $property->id,
    'klient_id' => $client->id,
    'rieltor_id' => $realtor->id,
    'tip' => 'sale',
    'tsena' => $property->tsena,
    'data_nachala' => now(),
    'status_dogovora_id' => $pendingContractStatus->id,
    'primechaniya' => 'Автотест pending',
]);

echo "done\n";
