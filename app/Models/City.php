<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Город из справочника goroda.
 *
 * Используется в объявлениях по gorod_id; есть кэш названий для быстрого отображения.
 */
class City extends Model
{
    use HasFactory;

    protected $table = 'goroda';

    const CREATED_AT = 'sozdano_at';
    const UPDATED_AT = 'obnovleno_at';

    protected $fillable = [
        'nazvanie',
    ];

    protected $casts = [
        'sozdano_at' => 'datetime',
        'obnovleno_at' => 'datetime',
    ];

    /** Кэш: id города → название (чтобы не дергать БД на каждое объявление) */
    private static ?array $nazvaniePoId = null;

    /** Название города по id; при первом вызове подгружает весь справочник */
    public static function nazvanieFor(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }
        if (self::$nazvaniePoId === null) {
            self::$nazvaniePoId = static::query()->orderBy('id')->pluck('nazvanie', 'id')->all();
        }

        return self::$nazvaniePoId[$id] ?? null;
    }

    /** Сбросить кэш (после добавления или изменения города) */
    public static function forgetNazvanieCache(): void
    {
        self::$nazvaniePoId = null;
    }

    /** Все объявления в этом городе */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'gorod_id');
    }
}
