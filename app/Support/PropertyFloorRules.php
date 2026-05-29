<?php

namespace App\Support;

/**
 * Проверка этажа и этажности здания при создании/редактировании объявления.
 */
final class PropertyFloorRules
{
    /** @return array<string, string> */
    public static function errors(array $data): array
    {
        if (($data['tip'] ?? '') === 'land') {
            return [];
        }

        $floor = self::intOrNull($data['etazh'] ?? null);
        $total = self::intOrNull($data['vsego_etazhey'] ?? null);

        $errors = [];

        if ($floor !== null && $floor < 1) {
            $errors['etazh'] = 'Укажите этаж не меньше 1.';
        }

        if ($total !== null && $total < 1) {
            $errors['vsego_etazhey'] = 'Укажите этажность дома не меньше 1.';
        }

        if ($floor !== null && $total !== null && $floor > $total) {
            $errors['etazh'] = 'Этаж (' . $floor . ') не может быть больше общего количества этажей (' . $total . ').';
        }

        return $errors;
    }

    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
