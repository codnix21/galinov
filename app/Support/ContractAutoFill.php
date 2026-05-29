<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;

/**
 * Автозаполнение договора по данным объекта и сторон сделки.
 */
class ContractAutoFill
{
    public static function defaultRealtor(): ?User
    {
        return User::query()
            ->whereHas('roleRelation', fn ($q) => $q->where('kod', 'realtor'))
            ->where(fn ($q) => $q->where('zablokirovan', false)->orWhereNull('zablokirovan'))
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array<string, mixed> Поля для Contract::create
     */
    public static function build(
        Property $property,
        User $owner,
        User $buyer,
        User $realtor,
        string $sozdalKak = 'client',
        ?string $sozdalStorona = 'buyer',
    ): array {
        $property->loadMissing('cityRelation');
        $operation = $property->operatsiya ?? $property->operation ?? 'sale';
        $address = trim(($property->gorod ?? '').', '.($property->adres_ulitsy ?? ''));

        $dataNachala = now()->toDateString();
        $dataOkonchaniya = null;
        if ($operation === 'rent') {
            $dataOkonchaniya = now()->addYear()->toDateString();
        }

        $notes = implode("\n", array_filter([
            'Договор сформирован автоматически по объекту №'.$property->id.'.',
            $property->nazvanie,
            $address !== ',' ? 'Адрес: '.$address : null,
            'Площадь: '.($property->ploshchad ? $property->ploshchad.' м²' : '—'),
            'Стороны подтверждают актуальность данных объявления на дату подписания.',
        ]));

        return [
            'nedvizhimost_id' => $property->id,
            'vladelets_id' => $owner->id,
            'pokupatel_id' => $buyer->id,
            'klient_id' => $buyer->id,
            'rieltor_id' => $realtor->id,
            'sozdal_kak' => $sozdalKak,
            'sozdal_storona' => $sozdalStorona,
            'tip' => $operation,
            'tsena' => $property->tsena,
            'data_nachala' => $dataNachala,
            'data_okonchaniya' => $dataOkonchaniya,
            'primechaniya' => $notes,
            'avto_zapolnen' => true,
            'oplata_status' => 'none',
        ];
    }

    public static function createPendingContract(
        Property $property,
        User $buyer,
        ?User $realtor = null,
        string $sozdalKak = 'client',
    ): Contract {
        $property->loadMissing('user');
        $owner = $property->user ?? User::find($property->polzovatel_id);
        if (!$owner) {
            throw new \InvalidArgumentException('У объекта не указан владелец');
        }

        if ((int) $owner->id === (int) $buyer->id) {
            throw new \InvalidArgumentException('Покупатель не может быть владельцем объекта');
        }

        $realtor = $realtor ?? self::defaultRealtor();
        if (!$realtor) {
            throw new \InvalidArgumentException('В системе нет риэлтора для оформления сделки');
        }

        $pendingStatus = ContractStatus::firstOrCreate(
            ['kod' => 'pending'],
            ['nazvanie' => 'На подтверждении']
        );

        $data = self::build($property, $owner, $buyer, $realtor, $sozdalKak, 'buyer');
        $data['status_dogovora_id'] = $pendingStatus->id;

        return Contract::create($data);
    }
}
