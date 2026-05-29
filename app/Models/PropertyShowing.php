<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Показ объекта клиенту (pokazy_nedvizhimosti).
 */
class PropertyShowing extends Model
{
    protected $table = 'pokazy_nedvizhimosti';

    protected $fillable = [
        'rieltor_id',
        'klient_id',
        'nedvizhimost_id',
        'naznacheno_na',
        'rezultat',
        'zametki',
    ];

    const CREATED_AT = 'sozdano_at';

    const UPDATED_AT = 'obnovleno_at';

    protected $casts = [
        'naznacheno_na' => 'datetime',
        'sozdano_at' => 'datetime',
        'obnovleno_at' => 'datetime',
    ];

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
