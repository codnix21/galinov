<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\User;
use App\Services\ContractEcpService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Логика согласования договора тремя участниками: владелец, покупатель, риэлтор.
 */
class ContractApproval
{
    /** Договор создан риэлтором — подтверждают владелец и покупатель */
    public static function createdByRealtor(Contract $contract): bool
    {
        return ($contract->sozdal_kak ?? '') === 'realtor';
    }

    /** Сторона клиента при создании: owner или buyer */
    public static function clientCreatorParty(Contract $contract): ?string
    {
        $s = $contract->sozdal_storona ?? null;
        if (in_array($s, ['owner', 'buyer'], true)) {
            return $s;
        }

        return null;
    }

    public static function ownerId(Contract $contract): ?int
    {
        $id = $contract->vladelets_id ?? null;

        return $id ? (int) $id : null;
    }

    public static function buyerId(Contract $contract): ?int
    {
        $id = $contract->pokupatel_id ?? null;

        return $id ? (int) $id : null;
    }

    public static function realtorId(Contract $contract): ?int
    {
        $id = $contract->rieltor_id ?? null;

        return $id ? (int) $id : null;
    }

    /** Нужны ли подтверждения от владельца, покупателя, риэлтора */
    public static function needsOwnerApproval(Contract $contract): bool
    {
        if (self::createdByRealtor($contract)) {
            return true;
        }

        return self::clientCreatorParty($contract) === 'buyer';
    }

    public static function needsBuyerApproval(Contract $contract): bool
    {
        if (self::createdByRealtor($contract)) {
            return true;
        }

        return self::clientCreatorParty($contract) === 'owner';
    }

    public static function needsRealtorApproval(Contract $contract): bool
    {
        return !self::createdByRealtor($contract);
    }

    public static function isOwnerApproved(Contract $contract): bool
    {
        return !self::needsOwnerApproval($contract) || $contract->podtverzhden_vladelets_at !== null;
    }

    public static function isBuyerApproved(Contract $contract): bool
    {
        return !self::needsBuyerApproval($contract) || $contract->podtverzhden_pokupatel_at !== null;
    }

    public static function isRealtorApproved(Contract $contract): bool
    {
        return !self::needsRealtorApproval($contract) || $contract->podtverzhden_rieltor_at !== null;
    }

    public static function isFullyApproved(Contract $contract): bool
    {
        return self::isOwnerApproved($contract)
            && self::isBuyerApproved($contract)
            && self::isRealtorApproved($contract);
    }

    /** Роль пользователя в договоре: owner, buyer, realtor или null */
    public static function partyRoleForUser(Contract $contract, User $user): ?string
    {
        $uid = (int) $user->id;
        if (self::ownerId($contract) === $uid) {
            return 'owner';
        }

        $contract->loadMissing('sellers');
        foreach ($contract->sellers as $seller) {
            if ((int) $seller->polzovatel_id === $uid) {
                return 'owner';
            }
        }
        if (self::buyerId($contract) === $uid) {
            return 'buyer';
        }
        if (self::realtorId($contract) === $uid) {
            return 'realtor';
        }

        return null;
    }

    public static function userCanApprove(User $user, Contract $contract): bool
    {
        if (($contract->status ?? '') !== 'pending') {
            return false;
        }

        $role = self::partyRoleForUser($contract, $user);
        if ($role === null) {
            return $user->isAdmin();
        }

        return match ($role) {
            'owner' => self::needsOwnerApproval($contract) && !self::isOwnerApproved($contract),
            'buyer' => self::needsBuyerApproval($contract) && !self::isBuyerApproved($contract),
            'realtor' => self::needsRealtorApproval($contract) && !self::isRealtorApproved($contract),
            default => false,
        };
    }

    /** Риэлтор/админ может подтвердить от имени недостающей стороны только если он не сторона */
    public static function staffCanApproveAsRealtor(User $user, Contract $contract): bool
    {
        return ($user->isRealtor() || $user->isAdmin())
            && self::needsRealtorApproval($contract)
            && !self::isRealtorApproved($contract);
    }

    public static function recordApproval(Contract $contract, User $user): void
    {
        $now = Carbon::now();
        $role = self::partyRoleForUser($contract, $user);

        if ($user->isAdmin() && $role === null) {
            if (self::needsRealtorApproval($contract) && !self::isRealtorApproved($contract)) {
                $contract->podtverzhden_rieltor_at = $now;
            }
            if (self::needsOwnerApproval($contract) && !self::isOwnerApproved($contract)) {
                $contract->podtverzhden_vladelets_at = $now;
            }
            if (self::needsBuyerApproval($contract) && !self::isBuyerApproved($contract)) {
                $contract->podtverzhden_pokupatel_at = $now;
            }

            return;
        }

        if ($role === 'owner' && self::needsOwnerApproval($contract)) {
            $contract->podtverzhden_vladelets_at = $now;
        } elseif ($role === 'buyer' && self::needsBuyerApproval($contract)) {
            $contract->podtverzhden_pokupatel_at = $now;
        } elseif ($role === 'realtor' && self::needsRealtorApproval($contract)) {
            $contract->podtverzhden_rieltor_at = $now;
        } elseif (($user->isRealtor() || $user->isAdmin()) && self::staffCanApproveAsRealtor($user, $contract)) {
            $contract->podtverzhden_rieltor_at = $now;
        }
    }

    public static function pendingSummary(Contract $contract): string
    {
        $parts = [];
        if (self::needsOwnerApproval($contract) && !self::isOwnerApproved($contract)) {
            $parts[] = 'владелец';
        }
        if (self::needsBuyerApproval($contract) && !self::isBuyerApproved($contract)) {
            $parts[] = 'покупатель';
        }
        if (self::needsRealtorApproval($contract) && !self::isRealtorApproved($contract)) {
            $parts[] = 'риэлтор';
        }

        return $parts === [] ? '—' : implode(', ', $parts);
    }

    public static function userIsParty(Contract $contract, User $user): bool
    {
        return self::partyRoleForUser($contract, $user) !== null;
    }

    /**
     * После подписания УКЭП всеми сторонами — согласование считается пройденным, договор активируется.
     */
    public static function finalizeFromEcp(Contract $contract): bool
    {
        if (!app(ContractEcpService::class)->isFullySigned($contract)) {
            return false;
        }

        $dirty = false;

        if (self::needsOwnerApproval($contract) && !$contract->podtverzhden_vladelets_at) {
            $ownerSignedAt = $contract->ecp_podpis_vladelets_at;
            if (!$ownerSignedAt && app(ContractEcpService::class)->allSellersSigned($contract)) {
                $contract->loadMissing('sellers');
                $ownerSignedAt = $contract->sellers->max('ecp_podpis_at');
            }
            $contract->podtverzhden_vladelets_at = $ownerSignedAt ?? Carbon::now();
            $dirty = true;
        }
        if (self::needsBuyerApproval($contract) && !$contract->podtverzhden_pokupatel_at) {
            $contract->podtverzhden_pokupatel_at = $contract->ecp_podpis_pokupatel_at ?? Carbon::now();
            $dirty = true;
        }
        if (self::needsRealtorApproval($contract) && !$contract->podtverzhden_rieltor_at) {
            $contract->podtverzhden_rieltor_at = $contract->ecp_podpis_rieltor_at ?? Carbon::now();
            $dirty = true;
        }

        if ($dirty) {
            $contract->save();
        }

        if (($contract->status ?? '') === 'pending' && self::isFullyApproved($contract->fresh())) {
            self::activateContract($contract->fresh());

            return true;
        }

        return $dirty;
    }

    public static function activateContract(Contract $contract): void
    {
        $activeStatus = ContractStatus::firstOrCreate(
            ['kod' => 'active'],
            ['nazvanie' => 'Активен']
        );

        $contract->update([
            'status_dogovora_id' => $activeStatus->id,
            'ozhidaet_podtverzhdeniya' => null,
        ]);

        $contractTip = $contract->tip ?? $contract->type;

        if ($contractTip === 'sale' && $contract->nedvizhimost_id) {
            $property = Property::find($contract->nedvizhimost_id);
            if ($property) {
                $soldStatus = PropertyStatus::firstOrCreate(['kod' => 'sold'], ['nazvanie' => 'Продано']);
                $property->update(['status_obyavleniya_id' => $soldStatus->id]);
                DB::table('izbrannoe')->where('nedvizhimost_id', $property->id)->delete();
            }
        } elseif ($contractTip === 'rent' && $contract->nedvizhimost_id) {
            $property = Property::find($contract->nedvizhimost_id);
            if ($property) {
                $rentedStatus = PropertyStatus::firstOrCreate(['kod' => 'rented'], ['nazvanie' => 'Сдано']);
                $property->update(['status_obyavleniya_id' => $rentedStatus->id]);
                DB::table('izbrannoe')->where('nedvizhimost_id', $property->id)->delete();
            }
            \App\Services\RentScheduleService::generateForContract($contract->fresh());
        }
    }

    /** Статус для интерфейса с учётом УКЭП. */
    public static function displayStatusName(Contract $contract): string
    {
        if (app(ContractEcpService::class)->isFullySigned($contract)) {
            if (($contract->status ?? '') === 'active') {
                return $contract->status_name;
            }

            return 'Подписан УКЭП';
        }

        return $contract->status_name;
    }
}
