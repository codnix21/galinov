<?php

namespace App\Support;

use App\Models\PropertyInquiry;
use App\Models\SystemSetting;
use Carbon\Carbon;

class InquirySla
{
    public const SETTING_KEY = 'inquiry_sla_hours';

    public static function hours(): int
    {
        $raw = SystemSetting::get(self::SETTING_KEY, '24');

        return max(1, (int) $raw);
    }

    public static function isOverdue(PropertyInquiry $inquiry): bool
    {
        if (($inquiry->status ?? '') !== 'new') {
            return false;
        }

        $created = $inquiry->sozdano_at ?? $inquiry->created_at;
        if (!$created) {
            return false;
        }

        return $created->copy()->addHours(self::hours())->isPast();
    }

    public static function deadlineAt(PropertyInquiry $inquiry): ?Carbon
    {
        $created = $inquiry->sozdano_at ?? $inquiry->created_at;
        if (!$created) {
            return null;
        }

        return $created->copy()->addHours(self::hours());
    }
}
