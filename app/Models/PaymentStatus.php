<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Справочник статусов оплаты по договору. */
class PaymentStatus extends Model
{
    protected $table = 'statusy_oplat';

    public $timestamps = false;

    protected $fillable = ['kod', 'nazvanie'];

    private static ?array $idPoKodu = null;

    private static ?array $kodPoId = null;

    public static function idFor(string $kod): ?int
    {
        self::loadCache();

        return self::$idPoKodu[$kod] ?? null;
    }

    public static function kodFor(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }
        self::loadCache();

        return self::$kodPoId[$id] ?? null;
    }

    public static function forgetCache(): void
    {
        self::$idPoKodu = null;
        self::$kodPoId = null;
    }

    private static function loadCache(): void
    {
        if (self::$idPoKodu !== null) {
            return;
        }
        $pairs = static::query()->orderBy('id')->get(['id', 'kod']);
        self::$idPoKodu = $pairs->pluck('id', 'kod')->all();
        self::$kodPoId = $pairs->pluck('kod', 'id')->all();
    }
}
