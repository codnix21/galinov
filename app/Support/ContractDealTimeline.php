<?php

namespace App\Support;

use App\Models\Contract;
use App\Services\ContractEcpService;

/**
 * Этапы сделки для отображения клиенту и в карточке договора.
 *
 * @phpstan-type TimelineStep array{key: string, label: string, done: bool, at: ?\Illuminate\Support\Carbon, hint: ?string, url: ?string}
 */
class ContractDealTimeline
{
    /** @return list<TimelineStep> */
    public static function build(Contract $contract): array
    {
        $contract->loadMissing(['property', 'owner', 'buyer', 'realtor', 'sellers.user', 'statusRelation']);
        $ecp = app(ContractEcpService::class);
        $isRent = ($contract->tip ?? '') === 'rent';
        $steps = [];

        $steps[] = [
            'key' => 'created',
            'label' => 'Договор сформирован',
            'done' => true,
            'at' => $contract->data_nachala ?? $contract->sozdano_at,
            'hint' => $contract->property?->nazvanie,
            'url' => route('contracts.show', $contract),
        ];

        $paid = $contract->isPaid();
        $steps[] = [
            'key' => 'payment',
            'label' => $paid ? 'Оплата получена' : 'Ожидается оплата',
            'done' => $paid,
            'at' => $contract->oplata_at,
            'hint' => $paid ? number_format((float) ($contract->oplata_summa ?? $contract->tsena), 0, ',', ' ').' ₽' : null,
            'url' => $paid ? null : route('purchase.payment', $contract),
        ];

        $sellersSigned = $ecp->allSellersSigned($contract);
        $sellerCount = $contract->resolvedSellers()->count();
        $steps[] = [
            'key' => 'ecp_sellers',
            'label' => $sellerCount > 1
                ? 'Подписи собственников ('.$sellerCount.')'
                : ($isRent ? 'Подпись арендодателя' : 'Подпись продавца'),
            'done' => $sellersSigned,
            'at' => self::latestSellerSignatureAt($contract),
            'hint' => $sellersSigned ? 'УКЭП' : 'Автоподпись после оплаты',
            'url' => route('contracts.show', $contract).'#ecp',
        ];

        $steps[] = [
            'key' => 'ecp_buyer',
            'label' => $isRent ? 'Подпись арендатора' : 'Подпись покупателя',
            'done' => $contract->ecp_podpis_pokupatel_at !== null,
            'at' => $contract->ecp_podpis_pokupatel_at,
            'hint' => null,
            'url' => route('contracts.show', $contract).'#ecp',
        ];

        $steps[] = [
            'key' => 'ecp_realtor',
            'label' => 'Подпись риэлтора',
            'done' => $contract->ecp_podpis_rieltor_at !== null,
            'at' => $contract->ecp_podpis_rieltor_at,
            'hint' => null,
            'url' => null,
        ];

        $fullyApproved = ContractApproval::isFullyApproved($contract);
        $steps[] = [
            'key' => 'approval',
            'label' => 'Согласование сторон',
            'done' => $fullyApproved,
            'at' => self::latestApprovalAt($contract),
            'hint' => $fullyApproved ? null : ContractApproval::pendingSummary($contract),
            'url' => route('contracts.show', $contract),
        ];

        $active = ($contract->status ?? '') === 'active';
        $steps[] = [
            'key' => 'active',
            'label' => 'Договор в силе',
            'done' => $active,
            'at' => $active ? ($contract->obnovleno_at ?? $contract->sozdano_at) : null,
            'hint' => $contract->status_name ?? null,
            'url' => null,
        ];

        $hasScan = !empty($contract->skan_dogovora);
        $steps[] = [
            'key' => 'scan',
            'label' => 'Скан подписанного договора',
            'done' => $hasScan,
            'at' => $hasScan ? $contract->obnovleno_at : null,
            'hint' => $hasScan ? 'Файл в системе' : 'Загружает риэлтор или администратор',
            'url' => $hasScan ? ($contract->skan_dogovora_url ?? null) : null,
        ];

        return $steps;
    }

    public static function progressPercent(array $steps): int
    {
        if ($steps === []) {
            return 0;
        }
        $done = count(array_filter($steps, fn (array $s) => $s['done']));

        return (int) round(($done / count($steps)) * 100);
    }

    private static function latestSellerSignatureAt(Contract $contract): ?\Illuminate\Support\Carbon
    {
        $contract->loadMissing('sellers');
        if ($contract->sellers->isNotEmpty()) {
            $max = $contract->sellers->max('ecp_podpis_at');

            return $max instanceof \Illuminate\Support\Carbon ? $max : null;
        }

        return $contract->ecp_podpis_vladelets_at;
    }

    private static function latestApprovalAt(Contract $contract): ?\Illuminate\Support\Carbon
    {
        $dates = array_filter([
            $contract->podtverzhden_vladelets_at,
            $contract->podtverzhden_pokupatel_at,
            $contract->podtverzhden_rieltor_at,
        ]);

        if ($dates === []) {
            return null;
        }

        return collect($dates)->sortDesc()->first();
    }
}
