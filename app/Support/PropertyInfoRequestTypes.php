<?php

namespace App\Support;

class PropertyInfoRequestTypes
{
    public const OWNERSHIP_YEARS = 'ownership_years';

    public const DOCUMENTS = 'documents';

    public const ENCUMBRANCE = 'encumbrance';

    public const SPOUSE_CONSENT = 'spouse_consent';

    public const OTHER = 'other';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::OWNERSHIP_YEARS => 'Сколько лет собственник владеет объектом',
            self::DOCUMENTS => 'Подтверждение документов',
            self::ENCUMBRANCE => 'Наличие обременений',
            self::SPOUSE_CONSENT => 'Согласие супругов',
            self::OTHER => 'Другой вопрос',
        ];
    }

    public static function label(?string $tip): string
    {
        if ($tip === null || $tip === '') {
            return 'Запрос';
        }

        return self::labels()[$tip] ?? $tip;
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::labels());
    }
}
