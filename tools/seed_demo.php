<?php

/**
 * Загрузка демо-данных (обход artisan при проблемах с путём проекта).
 * Запуск: php tools/seed_demo.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$seeder = new Database\Seeders\DemoDataSeeder();
$seeder->setContainer($app);
$seeder->run();

echo "seed_demo: ok\n";
