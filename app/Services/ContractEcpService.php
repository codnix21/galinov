<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractSeller;
use App\Models\User;
use App\Support\ContractApproval;

/**
 * Имитация усиленной квалифицированной ЭП (УКЭП).
 * В продакшене подключают КриптоПро, Такском, Госуслуги и т.д.
 */
class ContractEcpService
{
    public const PROVIDER = 'УКЭП';

    /** Автоподпись всех собственников (продавцов) и риэлтора. */
    public function autoSignOwnerAndRealtor(Contract $contract): Contract
    {
        $contract->loadMissing(['owner', 'realtor', 'sellers.user']);

        foreach ($contract->resolvedSellers() as $seller) {
            if (!$seller->user) {
                continue;
            }

            if ($seller instanceof ContractSeller && $seller->exists) {
                if (!$seller->ecp_podpis_at) {
                    $this->applySellerSignature($contract, $seller, $seller->user);
                }
            } elseif (!$contract->ecp_podpis_vladelets_at) {
                $this->applySignature($contract, $seller->user, 'vladelets', true);
            }
        }

        if ($contract->realtor && !$contract->ecp_podpis_rieltor_at) {
            $this->applySignature($contract, $contract->realtor, 'rieltor', true);
        }

        return $contract->fresh(['sellers.user']);
    }

    /** Подпись покупателем по кнопке в интерфейсе. */
    public function signAsBuyer(Contract $contract, User $buyer): Contract
    {
        if ((int) ContractApproval::buyerId($contract) !== (int) $buyer->id) {
            throw new \InvalidArgumentException('Подписать договор может только покупатель (арендатор) по сделке');
        }

        if ($contract->ecp_podpis_pokupatel_at) {
            throw new \InvalidArgumentException('Договор уже подписан покупателем');
        }

        $this->applySignature($contract, $buyer, 'pokupatel', false);

        return $contract->fresh();
    }

    public function isFullySigned(Contract $contract): bool
    {
        return $this->allSellersSigned($contract)
            && $contract->ecp_podpis_pokupatel_at
            && $contract->ecp_podpis_rieltor_at;
    }

    public function allSellersSigned(Contract $contract): bool
    {
        $contract->loadMissing(['sellers.user', 'owner']);
        $sellers = $contract->resolvedSellers();

        if ($contract->sellers->isNotEmpty()) {
            foreach ($contract->sellers as $seller) {
                if (!$seller->ecp_podpis_at) {
                    return false;
                }
            }

            return true;
        }

        if ($sellers->isEmpty()) {
            return false;
        }

        return $contract->ecp_podpis_vladelets_at !== null;
    }

    /** @return array{party: string, label: string, signed: bool, at: ?\Illuminate\Support\Carbon, nomera: ?string, fio: ?string, auto: bool}[] */
    public function signatureStatuses(Contract $contract): array
    {
        $contract->loadMissing(['owner', 'buyer', 'realtor', 'sellers.user']);
        $isRent = ($contract->tip ?? '') === 'rent';
        $statuses = [];

        $sellers = $contract->resolvedSellers();
        $usePerSeller = $contract->sellers->isNotEmpty() || $sellers->count() > 1;

        if ($usePerSeller) {
            foreach ($sellers->values() as $idx => $seller) {
                $share = $seller->dolya_procent
                    ? number_format((float) $seller->dolya_procent, 2, ',', ' ').' %'
                    : null;
                $label = $isRent ? 'Арендодатель' : 'Продавец (собственник)';
                if ($share) {
                    $label .= ' — доля '.$share;
                }
                if ($sellers->count() > 1) {
                    $label .= ' ('.($idx + 1).')';
                }

                $statuses[] = $this->sellerStatus($contract, $seller, $label);
            }
        } else {
            $statuses[] = $this->partyStatus(
                $contract,
                'vladelets',
                $isRent ? 'Арендодатель' : 'Продавец (собственник)',
                true
            );
        }

        $statuses[] = $this->partyStatus($contract, 'pokupatel', $isRent ? 'Арендатор' : 'Покупатель', false);
        $statuses[] = $this->partyStatus($contract, 'rieltor', 'Риэлтор агентства', true);

        return $statuses;
    }

    /**
     * @return array{party: string, label: string, signed: bool, at: ?\Illuminate\Support\Carbon, nomera: ?string, fio: ?string, auto: bool}
     */
    private function sellerStatus(Contract $contract, ContractSeller $seller, string $label): array
    {
        $at = $seller->ecp_podpis_at;
        $nom = $seller->ecp_podpis_nomera;
        $user = $seller->user;
        $fio = $user
            ? trim($user->familia.' '.$user->imya.' '.($user->otchestvo ?? ''))
            : $seller->fio();

        return [
            'party' => 'seller_'.$seller->polzovatel_id,
            'label' => $label,
            'signed' => $at !== null,
            'at' => $at,
            'nomera' => $nom,
            'fio' => $fio !== '' ? $fio : null,
            'auto' => $at !== null,
        ];
    }

    /**
     * @return array{party: string, label: string, signed: bool, at: ?\Illuminate\Support\Carbon, nomera: ?string, fio: ?string, auto: bool}
     */
    private function partyStatus(Contract $contract, string $party, string $label, bool $auto): array
    {
        $at = $contract->{'ecp_podpis_'.$party.'_at'};
        $nom = $contract->{'ecp_podpis_'.$party.'_nomera'};
        $fio = $this->partyFio($contract, $party);

        return [
            'party' => $party,
            'label' => $label,
            'signed' => $at !== null,
            'at' => $at,
            'nomera' => $nom,
            'fio' => $fio,
            'auto' => $auto && $at !== null,
        ];
    }

    private function applySellerSignature(Contract $contract, ContractSeller $seller, User $user): void
    {
        $nomera = $this->generateCertificateNumber($user, $contract);
        $now = now();

        $seller->update([
            'ecp_podpis_at' => $now,
            'ecp_podpis_nomera' => $nomera,
        ]);

        if ((int) $seller->polzovatel_id === (int) $contract->vladelets_id
            && !$contract->ecp_podpis_vladelets_at) {
            $contract->update([
                'ecp_podpis_vladelets_at' => $now,
                'ecp_podpis_vladelets_nomera' => $nomera,
            ]);
        }

        ContractApproval::finalizeFromEcp($contract->fresh(['sellers.user']));
    }

    private function applySignature(Contract $contract, User $user, string $party, bool $auto): void
    {
        $nomera = $this->generateCertificateNumber($user, $contract);

        $contract->update([
            'ecp_podpis_'.$party.'_at' => now(),
            'ecp_podpis_'.$party.'_nomera' => $nomera,
        ]);

        ContractApproval::finalizeFromEcp($contract->fresh());
    }

    private function partyFio(Contract $contract, string $party): ?string
    {
        $user = match ($party) {
            'vladelets' => $contract->owner,
            'pokupatel' => $contract->buyer,
            'rieltor' => $contract->realtor,
            default => null,
        };

        if (!$user) {
            return null;
        }

        return trim($user->familia.' '.$user->imya.' '.($user->otchestvo ?? ''));
    }

    public function generateCertificateNumber(User $user, Contract $contract): string
    {
        $hash = strtoupper(substr(hash('sha256', $user->id.'|'.$contract->id.'|'.config('app.key')), 0, 12));

        return 'УКЭП-'.$contract->id.'-'.$user->id.'-'.$hash;
    }
}
