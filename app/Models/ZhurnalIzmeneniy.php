<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

/**
 * Журнал изменений: кто, что и когда сделал с объявлением или договором.
 *
 * Таблица zhurnal_izmeneniy. obyekt_type + obyekt_id указывают на модель (Property, Contract…).
 */
class ZhurnalIzmeneniy extends Model
{
    /** Laravel не обновляет created_at/updated_at — дата только в sozdano_at */
    public $timestamps = false;

    protected $table = 'zhurnal_izmeneniy';

    protected $fillable = [
        'polzovatel_id',
        'obyekt_type',   // класс модели, например App\Models\Property
        'obyekt_id',
        'deystvie',      // код действия: created, updated, deleted…
        'detalizatsiya', // JSON с подробностями
        'kommentariy',
        'sozdano_at',
    ];

    protected $casts = [
        'detalizatsiya' => 'array', // при чтении — массив PHP
        'sozdano_at' => 'datetime',
    ];

    /** Пользователь, совершивший действие (может быть null для системных записей) */
    public function polzovatel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }

    /** Связанная сущность: объявление, договор и т.д. (полиморфная связь) */
    public function obyekt(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'obyekt_type', 'obyekt_id');
    }

    /**
     * Добавить запись в журнал (удобная обёртка над create).
     *
     * @param  array<string, mixed>|null  $detalizatsiya  Доп. данные (старое/новое значение и т.п.)
     */
    public static function zapisat(
        int|string|null $polzovatelId,
        string $obyektType,
        int $obyektId,
        string $deystvie,
        ?array $detalizatsiya = null,
        ?string $kommentariy = null
    ): self {
        $uid = match (true) {
            $polzovatelId === null, $polzovatelId === '' => null,
            is_numeric($polzovatelId) => (int) $polzovatelId,
            default => null,
        };

        return self::create([
            'polzovatel_id' => $uid,
            'obyekt_type' => $obyektType,
            'obyekt_id' => $obyektId,
            'deystvie' => $deystvie,
            'detalizatsiya' => $detalizatsiya,
            'kommentariy' => $kommentariy,
            'sozdano_at' => now(),
        ]);
    }

    /**
     * История по объявлению: записи по самому Property и по всем его договорам.
     * Используется на карточке объекта и в админке.
     */
    public static function istoriyaDlyaNedvizhimosti(Property $property, int $limit = 150): Collection
    {
        $contractIds = Contract::where('nedvizhimost_id', $property->id)->pluck('id');

        return self::query()
            ->with('polzovatel')
            ->where(function ($q) use ($property, $contractIds) {
                $q->where(function ($q2) use ($property) {
                    $q2->where('obyekt_type', Property::class)
                        ->where('obyekt_id', $property->id);
                });
                if ($contractIds->isNotEmpty()) {
                    $q->orWhere(function ($q3) use ($contractIds) {
                        $q3->where('obyekt_type', Contract::class)
                            ->whereIn('obyekt_id', $contractIds);
                    });
                }
            })
            ->orderByDesc('sozdano_at')
            ->limit($limit)
            ->get();
    }
}
