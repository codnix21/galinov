<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** История версий статуса бизнес-процесса (объявление, договор). */
class StatusVersion extends Model
{
    public $timestamps = false;

    protected $table = 'versii_statusov';

    protected $fillable = [
        'tip_sushchnosti',
        'sushchnost_id',
        'nomer_versii',
        'status_kod',
        'polzovatel_id',
        'kommentariy',
        'sozdano_at',
    ];

    protected $casts = [
        'sozdano_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }

    public function displayStatusLabel(): string
    {
        return match ($this->tip_sushchnosti) {
            'property' => PropertyStatus::query()->where('kod', $this->status_kod)->value('nazvanie'),
            'contract' => ContractStatus::query()->where('kod', $this->status_kod)->value('nazvanie'),
            default => null,
        } ?? $this->status_kod ?? '—';
    }
}
