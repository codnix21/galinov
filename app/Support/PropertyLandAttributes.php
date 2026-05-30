<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Параметры земельного участка (tip = land).
 */
class PropertyLandAttributes
{
    /** @var list<string> */
    public const BOOLEAN_FIELDS = [
        'internet',
        'vodosnabzhenie',
        'kanalizatsiya',
        'gaz',
        'zabor',
        'okhrana',
    ];

    /** @var array<string, string> */
    public const BOOLEAN_LABELS = [
        'internet' => 'Электричество / связь',
        'vodosnabzhenie' => 'Водоснабжение',
        'kanalizatsiya' => 'Канализация / септик',
        'gaz' => 'Газ',
        'zabor' => 'Ограждение',
        'okhrana' => 'Охрана / видеонаблюдение',
    ];

    public static function isLand(?string $tip): bool
    {
        return $tip === 'land';
    }

    /** @return array<string, string> */
    public static function validationRules(): array
    {
        $rules = [
            'ploshchad_uchastka' => 'nullable|numeric|min:0|max:99999',
        ];
        foreach (self::BOOLEAN_FIELDS as $field) {
            $rules[$field] = 'nullable|boolean';
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function mergeFromRequest(Request $request, array $validated): array
    {
        if (!self::isLand($validated['tip'] ?? null)) {
            return self::clearLandFields($validated);
        }

        foreach (self::BOOLEAN_FIELDS as $field) {
            $validated[$field] = $request->boolean($field);
        }

        if (!$request->filled('ploshchad_uchastka')) {
            $validated['ploshchad_uchastka'] = null;
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function clearLandFields(array $validated): array
    {
        if (self::isLand($validated['tip'] ?? null)) {
            return $validated;
        }

        if (PropertyHouseAttributes::isHouse($validated['tip'] ?? null)) {
            return $validated;
        }

        $validated['ploshchad_uchastka'] = null;
        foreach (self::BOOLEAN_FIELDS as $field) {
            $validated[$field] = null;
        }

        return $validated;
    }

    /** @return list<array{field: string, label: string, value: string}> */
    public static function displayRows(object $property): array
    {
        if (!self::isLand($property->tip ?? null)) {
            return [];
        }

        $rows = [];

        if ($property->ploshchad_uchastka !== null && $property->ploshchad_uchastka !== '') {
            $rows[] = [
                'field' => 'ploshchad_uchastka',
                'label' => 'Площадь участка',
                'value' => rtrim(rtrim(number_format((float) $property->ploshchad_uchastka, 2, ',', ' '), '0'), ',').' сот.',
            ];
        }

        if ($property->ploshchad) {
            $rows[] = [
                'field' => 'ploshchad',
                'label' => 'Площадь (по документам)',
                'value' => (string) $property->ploshchad.' м²',
            ];
        }

        foreach (self::BOOLEAN_FIELDS as $field) {
            if (!empty($property->{$field})) {
                $rows[] = [
                    'field' => $field,
                    'label' => self::BOOLEAN_LABELS[$field],
                    'value' => 'Да',
                ];
            }
        }

        return $rows;
    }
}
