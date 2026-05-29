<?php

namespace App\Support;

use App\Models\Property;
use App\Models\User;

/**
 * Примеры реквизитов документов для демо-сидера (без вызова DaData).
 */
class DemoDocumentData
{
    public static function cadastralForProperty(Property $property, int $salt = 0): string
    {
        $region = match ((int) ($property->gorod_id ?? 0)) {
            1 => '77',
            2 => '78',
            3 => '38',
            4 => '16',
            5 => '23',
            default => '38',
        };
        $q = str_pad((string) (($property->id * 17 + $salt * 3) % 9999999), 7, '0', STR_PAD_LEFT);
        $p = str_pad((string) (($property->id * 13 + $salt) % 9999), 4, '0', STR_PAD_LEFT);

        return "{$region}:01:{$q}:{$p}";
    }

    /**
     * @return array<string, string>
     */
    public static function sampleForTip(string $tip, Property $property, int $index = 0): array
    {
        $addr = trim(($property->gorod ?? '').', '.($property->adres_ulitsy ?? ''));

        return match ($tip) {
            'egrn', 'egrn_land' => [
                'kadastrovy_nomer' => self::cadastralForProperty($property, $index),
                'nomer_vypiski' => '77-01-'.sprintf('%07d', 1000000 + (int) $property->id + $index),
                'data_vypiski' => now()->subMonths(1 + ($index % 6))->toDateString(),
                'adres_obekta' => $addr,
            ],
            'ownership' => [
                'nomer_registracii' => '38-38-01/'.(100 + $property->id % 900).'/'.(2015 + $index % 10),
                'data_registracii' => now()->subYears(3 + ($index % 5))->toDateString(),
                'osnovanie' => 'Договор купли-продажи от '.now()->subYears(4)->format('d.m.Y'),
            ],
            'egrul' => [
                'ogrn' => '102380000'.str_pad((string) (1000 + $property->id % 9000), 4, '0', STR_PAD_LEFT),
                'inn_ul' => '3808'.str_pad((string) (1000000 + $property->id % 900000), 6, '0', STR_PAD_LEFT),
                'naimenovanie_yurlica' => 'ООО «Демо Коммерция '.$property->id.'»',
            ],
            'cadastral' => [
                'kadastrovy_nomer' => self::cadastralForProperty($property, $index + 1),
                'data_vypiski' => now()->subYears(2)->toDateString(),
            ],
            'rent_contract' => [
                'nomer_dogovora' => 'АР-'.(2020 + $index % 5).'/'.str_pad((string) $property->id, 4, '0', STR_PAD_LEFT),
                'data_nachala' => now()->subMonths(6)->toDateString(),
                'data_okonchaniya' => now()->addMonths(12)->toDateString(),
            ],
            'inn' => [
                'inn' => '3808'.str_pad((string) (1000000 + $index * 1111 % 900000), 6, '0', STR_PAD_LEFT),
                'snils' => sprintf('%03d-%03d-%03d %02d', 100 + $index % 800, 200 + $index % 700, 300 + $index % 600, 10 + $index % 89),
            ],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function personalDataForUser(User $user, int $index): array
    {
        $base = 1000 + ($index * 137) % 8000;

        return [
            'pasport_seriya_nomer' => sprintf('%04d %06d', $base, 100000 + ($index * 791) % 900000),
            'pasport_kem_vydan' => match ($index % 4) {
                0 => 'УМВД России по Иркутской области',
                1 => 'ГУ МВД России по г. Москве',
                2 => 'УМВД России по Республике Татарстан',
                default => 'УМВД России по Краснодарскому краю',
            },
            'pasport_data_vydachi' => now()->subYears(4 + ($index % 8))->subDays($index % 200)->toDateString(),
            'inn' => (string) (7700000000 + ($index * 1234567) % 999999999),
            'snils' => sprintf('%03d-%03d-%03d %02d', 100 + $index % 800, 150 + $index % 750, 200 + $index % 700, 10 + $index % 89),
        ];
    }
}
