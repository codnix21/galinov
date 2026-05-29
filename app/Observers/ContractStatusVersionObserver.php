<?php

namespace App\Observers;

use App\Models\Contract;
use App\Services\ProcessVersionService;
use Illuminate\Support\Facades\Auth;

class ContractStatusVersionObserver
{
    public function updated(Contract $contract): void
    {
        if (!$contract->wasChanged('status_dogovora_id')) {
            return;
        }

        ProcessVersionService::recordContract($contract, Auth::user());
    }

    public function created(Contract $contract): void
    {
        ProcessVersionService::recordContract($contract, Auth::user(), 'Создание договора');
    }
}
