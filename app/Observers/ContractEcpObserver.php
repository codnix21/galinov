<?php

namespace App\Observers;

use App\Models\Contract;
use App\Services\ContractEcpService;

/** Автоподпись УКЭП собственника и риэлтора при создании договора. */
class ContractEcpObserver
{
    public function created(Contract $contract): void
    {
        app(ContractEcpService::class)->autoSignOwnerAndRealtor($contract);
    }
}
