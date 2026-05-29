<?php

namespace App\Support;

/**
 * Справочники подписей CRM риэлтора.
 */
class RealtorCrm
{
    /** @return array<string, string> */
    public static function clientStatuses(): array
    {
        return [
            'new' => 'Новый',
            'in_progress' => 'В работе',
            'deal' => 'Сделка',
            'lost' => 'Отказ',
        ];
    }

    /** @return array<string, string> */
    public static function taskTypes(): array
    {
        return [
            'call' => 'Звонок',
            'meeting' => 'Встреча',
            'showing' => 'Показ',
            'other' => 'Другое',
        ];
    }

    /** @return array<string, string> */
    public static function showingResults(): array
    {
        return [
            '' => 'Не указан',
            'interested' => 'Заинтересован',
            'not_interested' => 'Не подошло',
            'no_show' => 'Не пришёл',
            'deal' => 'К сделке',
        ];
    }
}
