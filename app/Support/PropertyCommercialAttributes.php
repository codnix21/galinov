<?php

namespace App\Support;

use Illuminate\Http\Request;

class PropertyCommercialAttributes
{
    public const TIP_OFFICE = 'office';

    public const TIP_RETAIL = 'retail';

    public const TIP_WAREHOUSE = 'warehouse';

    public const TIP_PRODUCTION = 'production';

    /** @var array<string, string> */
    public const TIP_LABELS = [
        self::TIP_OFFICE => 'Офис',
        self::TIP_RETAIL => 'Торговое',
        self::TIP_WAREHOUSE => 'Склад',
        self::TIP_PRODUCTION => 'Производство',
    ];

    public static function isCommercial(?string $tip): bool
    {
        return $tip === 'commercial';
    }

    /** @return array<string, string> */
    public static function validationRules(): array
    {
        $tips = implode(',', array_keys(self::TIP_LABELS));

        return [
            'tip_pomeshcheniya' => 'nullable|in:'.$tips,
            'vysota_potolkov' => 'nullable|numeric|min:1|max:50',
            'otdelnyy_vhod' => 'nullable|boolean',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function mergeFromRequest(Request $request, array $validated): array
    {
        if (!self::isCommercial($validated['tip'] ?? null)) {
            return self::clearCommercialFields($validated);
        }

        $validated['otdelnyy_vhod'] = $request->boolean('otdelnyy_vhod');
        if (!$request->filled('tip_pomeshcheniya')) {
            $validated['tip_pomeshcheniya'] = null;
        }
        if (!$request->filled('vysota_potolkov')) {
            $validated['vysota_potolkov'] = null;
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function clearCommercialFields(array $validated): array
    {
        if (self::isCommercial($validated['tip'] ?? null)) {
            return $validated;
        }

        $validated['tip_pomeshcheniya'] = null;
        $validated['vysota_potolkov'] = null;
        $validated['otdelnyy_vhod'] = null;

        return $validated;
    }

    /** @return list<array{field: string, label: string, value: string}> */
    public static function displayRows(object $property): array
    {
        if (!self::isCommercial($property->tip ?? null)) {
            return [];
        }

        $rows = [];
        $tipLabel = self::TIP_LABELS[$property->tip_pomeshcheniya ?? ''] ?? null;
        if ($tipLabel) {
            $rows[] = ['field' => 'tip_pomeshcheniya', 'label' => 'Назначение', 'value' => $tipLabel];
        }
        if ($property->vysota_potolkov) {
            $rows[] = [
                'field' => 'vysota_potolkov',
                'label' => 'Высота потолков',
                'value' => rtrim(rtrim(number_format((float) $property->vysota_potolkov, 2, ',', ' '), '0'), ',').' м',
            ];
        }
        if (!empty($property->otdelnyy_vhod)) {
            $rows[] = ['field' => 'otdelnyy_vhod', 'label' => 'Отдельный вход', 'value' => 'Да'];
        }
        if (!empty($property->parking)) {
            $rows[] = ['field' => 'parking', 'label' => 'Парковка', 'value' => 'Да'];
        }
        if (!empty($property->internet)) {
            $rows[] = ['field' => 'internet', 'label' => 'Коммуникации', 'value' => 'Да'];
        }

        return $rows;
    }
}
