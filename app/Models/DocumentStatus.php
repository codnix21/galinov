<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Справочник статусов документов (dokumenty_proverki). */
class DocumentStatus extends Model
{
    protected $table = 'statusy_dokumentov';

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

    /** SQL FIELD(...) для сортировки очереди модерации. */
    public static function fieldOrderSql(string $column = 'status_dokumenta_id'): string
    {
        $ids = array_values(array_filter(array_map(
            fn (string $kod) => self::idFor($kod),
            ['pending', 'checking', 'rejected', 'verified'],
        )));

        if ($ids === []) {
            return "{$column} ASC";
        }

        return 'FIELD('.$column.', '.implode(', ', $ids).')';
    }
}
