<?php

namespace App\Support;

/**
 * Колонки импорта объявлений: русские заголовки в шаблоне и латинские ключи в БД.
 */
final class PropertyImportColumns
{
    /** @return list<array{key: string, label: string, required: bool, hint: string}> */
    public static function definitions(): array
    {
        return [
            ['key' => 'nazvanie', 'label' => 'Название', 'required' => true, 'hint' => 'Заголовок объявления'],
            ['key' => 'tsena', 'label' => 'Цена', 'required' => true, 'hint' => 'Число, без пробелов (₽ или ₽/мес для аренды)'],
            ['key' => 'gorod', 'label' => 'Город', 'required' => false, 'hint' => 'Например: Краснодар'],
            ['key' => 'adres', 'label' => 'Адрес', 'required' => false, 'hint' => 'Улица и дом, с номером'],
            ['key' => 'tip', 'label' => 'Тип', 'required' => false, 'hint' => 'apartment / house / commercial / land или квартира, дом…'],
            ['key' => 'operatsiya', 'label' => 'Операция', 'required' => false, 'hint' => 'sale (продажа) или rent (аренда)'],
            ['key' => 'status_kod', 'label' => 'Статус', 'required' => false, 'hint' => 'draft, active, pending_review…'],
            ['key' => 'email_vladelca', 'label' => 'Email владельца', 'required' => false, 'hint' => 'Если пусто — владелец из сессии админа'],
            ['key' => 'opisanie', 'label' => 'Описание', 'required' => false, 'hint' => 'Текст объявления'],
        ];
    }

    /** @return list<string> */
    public static function russianHeaders(): array
    {
        return array_map(fn (array $c) => $c['label'], self::definitions());
    }

    /** @return list<string> */
    public static function requiredKeys(): array
    {
        return array_values(array_map(
            fn (array $c) => $c['key'],
            array_filter(self::definitions(), fn (array $c) => $c['required'])
        ));
    }

    /** Нормализует заголовок колонки к внутреннему ключу (nazvanie, tsena…). */
    public static function normalizeHeader(string $raw): string
    {
        $h = mb_strtolower(trim($raw));
        $h = str_replace(['ё'], ['е'], $h);
        $h = preg_replace('/\s+/', '_', $h) ?? $h;

        foreach (self::definitions() as $col) {
            $key = $col['key'];
            $label = mb_strtolower($col['label']);
            $labelKey = preg_replace('/\s+/', '_', $label) ?? $label;

            if ($h === $key || $h === $labelKey) {
                return $key;
            }
        }

        $aliases = [
            'nazvanie' => ['nazvanie', 'название', 'заголовок', 'title', 'name'],
            'tsena' => ['tsena', 'цена', 'стоимость', 'price'],
            'gorod' => ['gorod', 'город', 'city'],
            'adres' => ['adres', 'адрес', 'address', 'adres_ulitsy', 'улица'],
            'tip' => ['tip', 'тип', 'type', 'тип_недвижимости'],
            'operatsiya' => ['operatsiya', 'операция', 'operation', 'сделка', 'тип_сделки'],
            'status_kod' => ['status_kod', 'статус', 'status', 'status_obyavleniya'],
            'email_vladelca' => ['email_vladelca', 'email', 'e-mail', 'почта', 'email_владельца', 'владелец'],
            'opisanie' => ['opisanie', 'описание', 'description', 'текст'],
        ];

        foreach ($aliases as $key => $list) {
            if (in_array($h, $list, true)) {
                return $key;
            }
        }

        return $h;
    }

    /** @return array<string, string> */
    public static function tipAliases(): array
    {
        return [
            'квартира' => 'apartment',
            'apartment' => 'apartment',
            'дом' => 'house',
            'house' => 'house',
            'коммерция' => 'commercial',
            'commercial' => 'commercial',
            'участок' => 'land',
            'land' => 'land',
        ];
    }

    /** @return array<string, string> */
    public static function operatsiyaAliases(): array
    {
        return [
            'продажа' => 'sale',
            'sale' => 'sale',
            'аренда' => 'rent',
            'rent' => 'rent',
            'сдача' => 'rent',
        ];
    }

    /** @return array<string, string> */
    public static function statusAliases(): array
    {
        return [
            'черновик' => 'draft',
            'draft' => 'draft',
            'активно' => 'active',
            'active' => 'active',
            'опубликовано' => 'active',
            'на_модерации' => 'pending_review',
            'модерация' => 'pending_review',
            'pending_review' => 'pending_review',
            'продано' => 'sold',
            'sold' => 'sold',
            'сдано' => 'rented',
            'rented' => 'rented',
            'снято' => 'inactive',
            'inactive' => 'inactive',
        ];
    }
}
