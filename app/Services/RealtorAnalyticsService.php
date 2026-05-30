<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\PropertySelectionRequest;
use App\Models\PropertyStatus;
use App\Models\RealtorClient;
use App\Support\RealtorScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RealtorAnalyticsService
{
    /** @return array<string, mixed> */
    public static function dashboard(int $realtorId, bool $allStaff = false): array
    {
        $from = Carbon::now()->subDays(30)->startOfDay();

        $propertiesQ = Property::query();
        if (!$allStaff) {
            $propertiesQ->where('rieltor_id', $realtorId);
        }

        $inquiriesQ = PropertyInquiry::query()->where('sozdano_at', '>=', $from);
        $selectionsQ = PropertySelectionRequest::query()->where('sozdano_at', '>=', $from);

        $activePid = PropertyStatus::idFor('active');
        $soldPid = PropertyStatus::idFor('sold');
        $activeCid = ContractStatus::idFor('active');

        $clientsQ = RealtorClient::query();
        if (!$allStaff) {
            RealtorScope::forRealtor($clientsQ);
        }

        $inquiriesTotal = (clone $inquiriesQ)->count();
        $inquiriesProcessed = (clone $inquiriesQ)->whereStatusKod('processed')->count();

        $contractsQ = Contract::query()->where('sozdano_at', '>=', $from);
        if (!$allStaff) {
            $contractsQ->where('rieltor_id', $realtorId);
        }

        return [
            'period_from' => $from->format('d.m.Y'),
            'properties_total' => (clone $propertiesQ)->count(),
            'properties_active' => $activePid ? (clone $propertiesQ)->where('status_obyavleniya_id', $activePid)->count() : 0,
            'properties_sold' => $soldPid ? (clone $propertiesQ)->where('status_obyavleniya_id', $soldPid)->count() : 0,
            'clients_total' => $clientsQ->count(),
            'inquiries_total' => $inquiriesTotal,
            'inquiries_conversion' => $inquiriesTotal > 0
                ? (int) round(($inquiriesProcessed / $inquiriesTotal) * 100) : 0,
            'selection_requests' => (clone $selectionsQ)->count(),
            'contracts_period' => (clone $contractsQ)->count(),
            'contracts_active' => $activeCid ? Contract::query()
                ->when(!$allStaff, fn ($q) => $q->where('rieltor_id', $realtorId))
                ->where('status_dogovora_id', $activeCid)->count() : 0,
            'inquiries_by_day' => (clone $inquiriesQ)
                ->select(DB::raw('DATE(sozdano_at) as d'), DB::raw('COUNT(*) as c'))
                ->groupBy('d')->orderBy('d')->pluck('c', 'd'),
        ];
    }
}
