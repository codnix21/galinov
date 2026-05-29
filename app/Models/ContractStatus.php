<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Справочник статусов договора (черновик, активен, завершён…).
 *
 * Таблица statusy_dogovorov. Поле kod — код для кода приложения, nazvanie — подпись в UI.
 */
class ContractStatus extends Model
{
    use HasFactory;

    protected $table = 'statusy_dogovorov';

    const CREATED_AT = 'sozdano_at';
    const UPDATED_AT = 'obnovleno_at';

    protected $fillable = [
        'kod',
        'nazvanie',
    ];

    protected $casts = [
        'sozdano_at' => 'datetime',
        'obnovleno_at' => 'datetime',
    ];

    /** Кэш: код → id записи в справочнике */
    private static ?array $idPoKodu = null;

    /** Кэш: id → код */
    private static ?array $kodPoId = null;

    /** Найти id статуса по коду (например active) */
    public static function idFor(string $kod): ?int
    {
        self::loadKodIdCache();

        return self::$idPoKodu[$kod] ?? null;
    }

    /** Найти код статуса по id из БД */
    public static function kodFor(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }
        self::loadKodIdCache();

        return self::$kodPoId[$id] ?? null;
    }

    /** Сбросить кэш код ↔ id */
    public static function forgetKodIdCache(): void
    {
        self::$idPoKodu = null;
        self::$kodPoId = null;
    }

    /** Один раз загрузить все пары kod/id из таблицы */
    private static function loadKodIdCache(): void
    {
        if (self::$idPoKodu !== null) {
            return;
        }
        $pairs = static::query()->orderBy('id')->get(['id', 'kod']);
        self::$idPoKodu = $pairs->pluck('id', 'kod')->all();
        self::$kodPoId = $pairs->pluck('kod', 'id')->all();
    }

    /** Договоры с этим статусом */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'status_dogovora_id');
    }
}
