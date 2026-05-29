<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Property;
use App\Models\StatusVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class ProcessVersionService
{
    public static function recordProperty(Property $property, ?User $actor = null, ?string $comment = null): void
    {
        $property->loadMissing('statusRelation');
        self::record(
            'property',
            (int) $property->id,
            $property->statusRelation?->kod,
            $property->statusRelation?->nazvanie ?? $property->status_name,
            $actor,
            $comment,
        );
    }

    public static function recordContract(Contract $contract, ?User $actor = null, ?string $comment = null): void
    {
        $contract->loadMissing('statusRelation');
        self::record(
            'contract',
            (int) $contract->id,
            $contract->statusRelation?->kod ?? $contract->status,
            $contract->statusRelation?->nazvanie ?? $contract->status_name,
            $actor,
            $comment,
        );
    }

    public static function record(
        string $entityType,
        int $entityId,
        ?string $statusKod,
        ?string $statusName,
        ?User $actor = null,
        ?string $comment = null,
    ): void {
        if (!Schema::hasTable('versii_statusov')) {
            return;
        }

        $lastVersion = StatusVersion::query()
            ->where('tip_sushchnosti', $entityType)
            ->where('sushchnost_id', $entityId)
            ->max('nomer_versii');

        StatusVersion::create([
            'tip_sushchnosti' => $entityType,
            'sushchnost_id' => $entityId,
            'nomer_versii' => ((int) $lastVersion) + 1,
            'status_kod' => $statusKod,
            'status_nazvanie' => $statusName,
            'polzovatel_id' => $actor?->id,
            'kommentariy' => $comment,
            'sozdano_at' => now(),
        ]);
    }

    /** @return Collection<int, StatusVersion> */
    public static function history(string $entityType, int $entityId): Collection
    {
        if (!Schema::hasTable('versii_statusov')) {
            return new Collection;
        }

        return StatusVersion::query()
            ->with('user')
            ->where('tip_sushchnosti', $entityType)
            ->where('sushchnost_id', $entityId)
            ->orderByDesc('nomer_versii')
            ->get();
    }
}
