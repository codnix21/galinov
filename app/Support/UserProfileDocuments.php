<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserDocument;

/**
 * Статус документов в профиле клиента (паспорт, ИНН).
 */
class UserProfileDocuments
{
    /** @return list<string> */
    public static function verifiedTips(int $userId): array
    {
        return UserDocument::query()
            ->whereNull('nedvizhimost_id')
            ->where('polzovatel_id', $userId)
            ->where('status', 'verified')
            ->pluck('tip')
            ->all();
    }

    public static function passportVerified(int $userId): bool
    {
        return in_array('passport', self::verifiedTips($userId), true);
    }

    public static function innVerified(int $userId): bool
    {
        return in_array('inn', self::verifiedTips($userId), true);
    }

    /**
     * Минимум для создания объявлений: проверенный паспорт в профиле.
     */
    public static function isReadyForListing(User $user): bool
    {
        if (!$user->isClient() && !$user->isRealtor()) {
            return true;
        }

        return self::passportVerified((int) $user->id);
    }

    /**
     * @return array{passport: string, inn: string, ready: bool, passport_verified: bool, inn_verified: bool}
     */
    public static function summary(User $user): array
    {
        $userId = (int) $user->id;
        $passportVerified = self::passportVerified($userId);
        $innVerified = self::innVerified($userId);

        return [
            'passport_verified' => $passportVerified,
            'inn_verified' => $innVerified,
            'passport' => self::statusLabel($userId, 'passport', $passportVerified),
            'inn' => self::statusLabel($userId, 'inn', $innVerified),
            'ready' => $passportVerified,
        ];
    }

    private static function statusLabel(int $userId, string $tip, bool $verified): string
    {
        if ($verified) {
            return 'verified';
        }

        $latest = UserDocument::query()
            ->whereNull('nedvizhimost_id')
            ->where('polzovatel_id', $userId)
            ->where('tip', $tip)
            ->orderByDesc('sozdano_at')
            ->first();

        if (!$latest) {
            return 'missing';
        }

        return match ($latest->status) {
            'rejected' => 'rejected',
            'checking', 'pending' => 'pending',
            default => 'pending',
        };
    }

    public static function statusText(string $status): string
    {
        return match ($status) {
            'verified' => 'Проверен',
            'rejected' => 'Отклонён',
            'pending', 'checking' => 'На проверке',
            default => 'Не загружен',
        };
    }

    /**
     * Заполнены ли текстовые поля паспорта в профиле (для договора).
     */
    public static function hasPersonalDataFilled(User $user): bool
    {
        $pd = $user->personalData;
        if (!$pd) {
            return false;
        }

        return filled($pd->pasport_seriya_nomer)
            && filled($pd->pasport_kem_vydan)
            && $pd->pasport_data_vydachi !== null;
    }
}
