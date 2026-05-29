<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Задача риэлтора (zadachi_rieltora).
 */
class RealtorTask extends Model
{
    protected $table = 'zadachi_rieltora';

    protected $fillable = [
        'rieltor_id',
        'klient_id',
        'nedvizhimost_id',
        'nazvanie',
        'opisanie',
        'tip',
        'srok_do',
        'vypolneno_at',
    ];

    const CREATED_AT = 'sozdano_at';

    const UPDATED_AT = 'obnovleno_at';

    protected $casts = [
        'srok_do' => 'datetime',
        'vypolneno_at' => 'datetime',
        'sozdano_at' => 'datetime',
        'obnovleno_at' => 'datetime',
    ];

    public function isDone(): bool
    {
        return $this->vypolneno_at !== null;
    }

    public function realtor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rieltor_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'klient_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'nedvizhimost_id');
    }
}
