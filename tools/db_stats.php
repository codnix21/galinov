<?php

/**
 * Счётчики в БД: всего объявлений и сколько в статусе «активно».
 * Запуск: php tools/db_stats.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\PropertyStatus;

$activeId = PropertyStatus::idFor('active');
$active = $activeId
    ? Property::where('status_obyavleniya_id', $activeId)->count()
    : 0;

echo 'total_properties=' . Property::count() . PHP_EOL;
echo 'active_properties=' . $active . PHP_EOL;
