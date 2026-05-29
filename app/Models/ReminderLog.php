<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Журнал отправленных напоминаний (без дублей в один день).
 */
class ReminderLog extends Model
{
    public $timestamps = false;

    protected $table = 'zhurnal_napominaniy';

    protected $fillable = [
        'tip',
        'entity_type',
        'entity_id',
        'den',
    ];

    protected $casts = [
        'den' => 'date',
        'sozdano_at' => 'datetime',
    ];

    public static function alreadySent(string $tip, string $entityType, int $entityId): bool
    {
        return self::query()
            ->where('tip', $tip)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('den', now()->toDateString())
            ->exists();
    }

    public static function markSent(string $tip, string $entityType, int $entityId): void
    {
        self::firstOrCreate([
            'tip' => $tip,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'den' => now()->toDateString(),
        ]);
    }
}
