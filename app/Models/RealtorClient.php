<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Закрепление клиента за риэлтором (klienty_rieltora).
 */
class RealtorClient extends Model
{
    protected $table = 'klienty_rieltora';

    protected $fillable = [
        'rieltor_id',
        'klient_id',
        'status',
        'zametki',
    ];

    const CREATED_AT = 'sozdano_at';

    const UPDATED_AT = 'obnovleno_at';

    protected $casts = [
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

    public function tasks(): HasMany
    {
        return $this->hasMany(RealtorTask::class, 'klient_id', 'klient_id')
            ->where('rieltor_id', $this->rieltor_id);
    }

    public function showings(): HasMany
    {
        return $this->hasMany(PropertyShowing::class, 'klient_id', 'klient_id')
            ->where('rieltor_id', $this->rieltor_id);
    }
}
