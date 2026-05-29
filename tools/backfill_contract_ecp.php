<?php

/**
 * Автоподпись УКЭП для собственника и риэлтора по существующим договорам.
 * php tools/backfill_contract_ecp.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Contract;
use App\Services\ContractEcpService;

$ecp = app(ContractEcpService::class);
$n = 0;
foreach (Contract::query()->cursor() as $contract) {
    $before = $contract->ecp_podpis_vladelets_at && $contract->ecp_podpis_rieltor_at;
    $ecp->autoSignOwnerAndRealtor($contract);
    if (!$before) {
        $n++;
    }
}
echo "Обработано договоров (доп. автоподпись): {$n}" . PHP_EOL;
