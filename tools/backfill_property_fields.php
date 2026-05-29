<?php

/**
 * Заполнить komnaty, etazh, vsego_etazhey у объявлений с пустыми полями.
 * php tools/backfill_property_fields.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$seeder = new Database\Seeders\DemoDataSeeder();
$updated = $seeder->backfillPropertyCharacteristics();

echo "Обновлено объявлений: {$updated}" . PHP_EOL;
echo 'komnaty null: ' . App\Models\Property::whereNull('komnaty')->count() . PHP_EOL;
echo 'etazh null: ' . App\Models\Property::whereNull('etazh')->count() . PHP_EOL;
