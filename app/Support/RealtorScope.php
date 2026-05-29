<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Ограничение CRM-запросов риэлтором (админ видит всё).
 */
class RealtorScope
{
    public static function currentRealtorId(): int
    {
        $user = Auth::user();

        return $user ? (int) $user->getKey() : 0;
    }

    public static function isAgencyView(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    /** @param  Builder<\Illuminate\Database\Eloquent\Model>  $query */
    public static function forRealtor(Builder $query, string $column = 'rieltor_id'): Builder
    {
        if (self::isAgencyView()) {
            return $query;
        }

        return $query->where($column, self::currentRealtorId());
    }

    public static function assertRealtorOwns(int $rieltorId): void
    {
        if (self::isAgencyView()) {
            return;
        }

        if ($rieltorId !== self::currentRealtorId()) {
            abort(403);
        }
    }

    public static function assertClientRole(User $user): void
    {
        if (!$user->isClient()) {
            abort(422, 'Можно закрепить только пользователя с ролью «Клиент»');
        }
    }
}
