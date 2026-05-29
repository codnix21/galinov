<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\User;
use App\Support\ContractApproval;
use Illuminate\Support\Str;

/**
 * Имитация усиленной квалифицированной ЭП (УКЭП) для дипломного проекта.
 * В продакшене подключают КриптоПро, Такском, Госуслуги и т.д.
 */
class ContractEcpService
{
    public const PROVIDER = 'УКЭП (демо)';

    /** Автоподпись владельца и риэлтора при создании договора. */
    public function autoSignOwnerAndRealtor(Contract $contract): Contract
    {
        $contract->loadMissing(['owner', 'realtor']);

        if ($contract->owner && !$contract->ecp_podpis_vladelets_at) {
            $this->applySignature($contract, $contract->owner, 'vladelets', true);
        }

        if ($contract->realtor && !$contract->ecp_podpis_rieltor_at) {
            $this->applySignature($contract, $contract->realtor, 'rieltor', true);
        }

        return $contract->fresh();
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
        return $contract->ecp_podpis_vladelets_at
            && $contract->ecp_podpis_pokupatel_at
            && $contract->ecp_podpis_rieltor_at;
    }

    /** @return array{party: string, label: string, signed: bool, at: ?\Illuminate\Support\Carbon, nomera: ?string, fio: ?string, auto: bool}[] */
    public function signatureStatuses(Contract $contract): array
    {
        $contract->loadMissing(['owner', 'buyer', 'realtor']);
        $isRent = ($contract->tip ?? '') === 'rent';

        return [
            $this->partyStatus($contract, 'vladelets', $isRent ? 'Арендодатель' : 'Продавец (собственник)', true),
            $this->partyStatus($contract, 'pokupatel', $isRent ? 'Арендатор' : 'Покупатель', false),
            $this->partyStatus($contract, 'rieltor', 'Риэлтор агентства', true),
        ];
    }

    /**
     * @return array{party: string, label: string, signed: bool, at: ?\Illuminate\Support\Carbon, nomera: ?string, fio: ?string, auto: bool}
     */
    private function partyStatus(Contract $contract, string $party, string $label, bool $auto): array
    {
        $at = $contract->{'ecp_podpis_' . $party . '_at'};
        $nom = $contract->{'ecp_podpis_' . $party . '_nomera'};
        $fio = $contract->{'ecp_podpis_' . $party . '_fio'};

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

    private function applySignature(Contract $contract, User $user, string $party, bool $auto): void
    {
        $fio = trim($user->familia . ' ' . $user->imya . ' ' . ($user->otchestvo ?? ''));
        $nomera = $this->generateCertificateNumber($user, $contract);

        $contract->update([
            'ecp_podpis_' . $party . '_at' => now(),
            'ecp_podpis_' . $party . '_nomera' => $nomera,
            'ecp_podpis_' . $party . '_fio' => $fio,
        ]);

        ContractApproval::finalizeFromEcp($contract->fresh());
    }

    public function generateCertificateNumber(User $user, Contract $contract): string
    {
        $hash = strtoupper(substr(hash('sha256', $user->id . '|' . $contract->id . '|' . config('app.key')), 0, 12));

        return 'УКЭП-' . $contract->id . '-' . $user->id . '-' . $hash;
    }
}
