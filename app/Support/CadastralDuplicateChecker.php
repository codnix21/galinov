<?php

namespace App\Support;

use App\Models\Property;
use App\Models\PropertyStatus;
use Illuminate\Support\Collection;

class CadastralDuplicateChecker
{
    public static function normalize(?string $nomer): ?string
    {
        if ($nomer === null || trim($nomer) === '') {
            return null;
        }

        return preg_replace('/\s+/u', '', mb_strtoupper(trim($nomer))) ?: null;
    }

    /** @return Collection<int, Property> */
    public static function findDuplicates(?string $kadastrovyNomer, ?int $exceptPropertyId = null): Collection
    {
        $norm = self::normalize($kadastrovyNomer);
        if ($norm === null) {
            return collect();
        }

        $activeId = PropertyStatus::idFor('active');
        $pendingId = PropertyStatus::idFor('pending_review');

        $query = Property::query()
            ->whereNotNull('kadastrovy_nomer')
            ->where('kadastrovy_nomer', '!=', '');

        if ($exceptPropertyId) {
            $query->where('id', '!=', $exceptPropertyId);
        }

        return $query->get()->filter(function (Property $p) use ($norm) {
            return self::normalize($p->kadastrovy_nomer) === $norm;
        })->filter(function (Property $p) use ($activeId, $pendingId) {
            $sid = (int) ($p->status_obyavleniya_id ?? 0);
            if ($activeId && $sid === (int) $activeId) {
                return true;
            }
            if ($pendingId && $sid === (int) $pendingId) {
                return true;
            }

            return false;
        })->values();
    }
}
