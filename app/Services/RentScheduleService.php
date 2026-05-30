<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\RentPayment;
use Carbon\Carbon;

class RentScheduleService
{
    /** Создать ежемесячные платежи на срок аренды. */
    public static function generateForContract(Contract $contract): int
    {
        if (($contract->tip ?? '') !== 'rent') {
            return 0;
        }

        $start = $contract->data_nachala ?? now();
        $end = $contract->data_okonchaniya;
        if (!$end) {
            $end = $start->copy()->addMonths(11);
        }

        $monthly = (float) ($contract->tsena ?? 0);
        if ($monthly <= 0) {
            return 0;
        }

        RentPayment::where('dogovor_id', $contract->id)->delete();

        $order = 0;
        $cursor = $start->copy()->startOfMonth();
        $created = 0;

        while ($cursor <= $end) {
            RentPayment::create([
                'dogovor_id' => $contract->id,
                'data_platezha' => $cursor->copy(),
                'summa' => $monthly,
                'status' => 'pending',
                'poryadok' => $order++,
            ]);
            $created++;
            $cursor->addMonth();
        }

        return $created;
    }

    public static function markPaid(RentPayment $payment): void
    {
        $payment->update([
            'status' => 'paid',
            'oplacheno_at' => now(),
        ]);
    }
}
