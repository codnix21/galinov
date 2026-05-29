<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Статусы заявок и CRM (gruppa: inquiry | selection | crm | info).
 */
class RequestStatus extends Model
{
    protected $table = 'statusy_zayavok';

    public $timestamps = false;

    protected $fillable = ['gruppa', 'kod', 'nazvanie'];

    /** @var array<string, array<string, int>> */
    private static array $idCache = [];

    /** @var array<string, array<int, string>> */
    private static array $kodCache = [];

    public static function idFor(string $gruppa, string $kod): ?int
    {
        self::loadGruppa($gruppa);

        return self::$idCache[$gruppa][$kod] ?? null;
    }

    public static function kodFor(string $gruppa, ?int $id): ?string
    {
        if ($id === null) {
            return null;
        }
        self::loadGruppa($gruppa);

        return self::$kodCache[$gruppa][$id] ?? null;
    }

    public static function nazvanieFor(string $gruppa, ?int $id): ?string
    {
        $kod = self::kodFor($gruppa, $id);

        return $kod
            ? static::query()->where('gruppa', $gruppa)->where('kod', $kod)->value('nazvanie')
            : null;
    }

    public static function forgetCache(): void
    {
        self::$idCache = [];
        self::$kodCache = [];
    }

    private static function loadGruppa(string $gruppa): void
    {
        if (isset(self::$idCache[$gruppa])) {
            return;
        }
        $rows = static::query()->where('gruppa', $gruppa)->orderBy('id')->get(['id', 'kod']);
        self::$idCache[$gruppa] = $rows->pluck('id', 'kod')->all();
        self::$kodCache[$gruppa] = $rows->pluck('kod', 'id')->all();
    }

    /** @param  list<string>  $kody */
    public static function fieldOrderSql(string $gruppa, array $kody, string $column = 'status_zayavki_id'): string
    {
        $ids = array_values(array_filter(array_map(
            fn (string $kod) => self::idFor($gruppa, $kod),
            $kody,
        )));

        if ($ids === []) {
            return "{$column} ASC";
        }

        return 'FIELD('.$column.', '.implode(', ', $ids).')';
    }
}
