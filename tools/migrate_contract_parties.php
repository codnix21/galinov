<?php

/**
 * Однократный запуск миграции сторон договора.
 * Из корня проекта: php tools/migrate_contract_parties.php
 */

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require_once $root.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$status = $kernel->call('migrate', [
    '--force' => true,
    '--path' => 'database/migrations/2026_05_22_140000_contract_parties_and_approvals.php',
]);

echo $kernel->output();

exit($status);
