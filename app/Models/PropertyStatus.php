<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Справочник статусов объявления (черновик, на модерации, активно…).
 *
 * Таблица statusy_obyavleniy. Связь с объявлениями через status_obyavleniya_id.
 */
class PropertyStatus extends Model
{
    use HasFactory;

    protected $table = 'statusy_obyavleniy';

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

    /** Кэш соответствия код ↔ id (как в ContractStatus) */
    private static ?array $idPoKodu = null;

    private static ?array $kodPoId = null;

    /** id записи по коду (draft, active…) */
    public static function idFor(string $kod): ?int
    {
        self::loadKodIdCache();

        return self::$idPoKodu[$kod] ?? null;
    }

    /** Код по id из справочника */
    public static function kodFor(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }
        self::loadKodIdCache();

        return self::$kodPoId[$id] ?? null;
    }

    /** Сбросить кэш после изменения справочника */
    public static function forgetKodIdCache(): void
    {
        self::$idPoKodu = null;
        self::$kodPoId = null;
    }

    private static function loadKodIdCache(): void
    {
        if (self::$idPoKodu !== null) {
            return;
        }
        $pairs = static::query()->orderBy('id')->get(['id', 'kod']);
        self::$idPoKodu = $pairs->pluck('id', 'kod')->all();
        self::$kodPoId = $pairs->pluck('kod', 'id')->all();
    }

    /** Объявления в этом статусе */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'status_obyavleniya_id');
    }
}
