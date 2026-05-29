<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractSeller;
use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PropertyOwnersService
{
    /** @return array<string, string> */
    public static function validateRows(array $rows): array
    {
        $errors = [];
        $filtered = array_values(array_filter($rows, fn ($r) => !empty($r['polzovatel_id'])));

        if ($filtered === []) {
            return [];
        }

        $sum = 0.0;
        $seenUsers = [];
        foreach ($filtered as $i => $row) {
            $uid = (int) ($row['polzovatel_id'] ?? 0);
            $share = (float) ($row['dolya_procent'] ?? 0);

            if ($uid <= 0) {
                $errors["owners.{$i}.polzovatel_id"] = 'Выберите собственника из списка.';

                continue;
            }

            if (isset($seenUsers[$uid])) {
                $errors["owners.{$i}.polzovatel_id"] = 'Один и тот же собственник указан дважды.';
            }
            $seenUsers[$uid] = true;

            if ($share <= 0 || $share > 100) {
                $errors["owners.{$i}.dolya_procent"] = 'Доля должна быть от 0,01 до 100 %.';
            }

            $sum += $share;
        }

        if ($errors !== []) {
            return $errors;
        }

        if (abs($sum - 100) > 0.02) {
            $errors['owners'] = sprintf(
                'Сумма долей собственников должна быть 100 %% (сейчас %.2f %%).',
                $sum
            );
        }

        $mainCount = count(array_filter($filtered, fn ($r) => !empty($r['osnovnoy'])));
        if ($mainCount > 1) {
            $errors['owners'] = 'Основным собственником может быть только один человек.';
        }

        return array_filter($errors);
    }

    /**
     * @param  list<array{polzovatel_id: int|string, dolya_procent: float|string, osnovnoy?: bool|int|string}>  $rows
     */
    public static function sync(Property $property, array $rows): void
    {
        $normalized = [];
        foreach (array_values($rows) as $i => $row) {
            $uid = (int) ($row['polzovatel_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $normalized[] = [
                'polzovatel_id' => $uid,
                'dolya_procent' => round((float) ($row['dolya_procent'] ?? 0), 2),
                'osnovnoy' => !empty($row['osnovnoy']),
                'poryadok' => $i,
            ];
        }

        if ($normalized === []) {
            self::ensureDefaultOwner($property);

            return;
        }

        if (count(array_filter($normalized, fn ($r) => $r['osnovnoy'])) === 0) {
            $normalized[0]['osnovnoy'] = true;
        }

        DB::transaction(function () use ($property, $normalized) {
            PropertyOwner::where('nedvizhimost_id', $property->id)->delete();
            foreach ($normalized as $row) {
                PropertyOwner::create([
                    'nedvizhimost_id' => $property->id,
                    'polzovatel_id' => $row['polzovatel_id'],
                    'dolya_procent' => $row['dolya_procent'],
                    'osnovnoy' => $row['osnovnoy'],
                    'poryadok' => $row['poryadok'],
                ]);
            }
        });

        $main = self::mainOwner($property->fresh(['owners']));
        if ($main) {
            $property->update(['polzovatel_id' => $main->polzovatel_id]);
        }
    }

    public static function ensureDefaultOwner(Property $property): void
    {
        $property->loadMissing('owners');
        if ($property->owners->isNotEmpty()) {
            return;
        }

        $ownerId = (int) ($property->polzovatel_id ?? 0);
        if ($ownerId <= 0) {
            return;
        }

        PropertyOwner::create([
            'nedvizhimost_id' => $property->id,
            'polzovatel_id' => $ownerId,
            'dolya_procent' => 100,
            'osnovnoy' => true,
            'poryadok' => 0,
        ]);
    }

    public static function mainOwner(Property $property): ?PropertyOwner
    {
        $property->loadMissing(['owners.user']);

        $main = $property->owners->firstWhere('osnovnoy', true)
            ?? $property->owners->sortBy('poryadok')->first();

        return $main;
    }

    public static function mainOwnerUser(Property $property): ?User
    {
        $row = self::mainOwner($property);

        return $row?->user;
    }

    /** @return Collection<int, int> */
    public static function ownerUserIds(Property $property): Collection
    {
        $property->loadMissing('owners');

        if ($property->owners->isNotEmpty()) {
            return $property->owners->pluck('polzovatel_id')->map(fn ($id) => (int) $id);
        }

        $fallback = (int) ($property->polzovatel_id ?? 0);

        return $fallback > 0 ? collect([$fallback]) : collect();
    }

    public static function copySellersToContract(Contract $contract, ?Property $property = null): void
    {
        $property ??= $contract->property;
        if (!$property) {
            return;
        }

        $property->loadMissing(['owners.user']);
        self::ensureDefaultOwner($property);
        $property->refresh();
        $property->load('owners.user');

        DB::transaction(function () use ($contract, $property) {
            ContractSeller::where('dogovor_id', $contract->id)->delete();

            foreach ($property->owners as $owner) {
                $user = $owner->user;
                ContractSeller::create([
                    'dogovor_id' => $contract->id,
                    'polzovatel_id' => $owner->polzovatel_id,
                    'dolya_procent' => $owner->dolya_procent,
                    'poryadok' => $owner->poryadok,
                ]);
            }
        });
    }

    public static function buyerAmongOwners(Property $property, int $buyerId): bool
    {
        return self::ownerUserIds($property)->contains($buyerId);
    }
}
