<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyInfoRequestMessage extends Model
{
    protected $table = 'zaprosy_dop_soobshcheniya';

    public $timestamps = false;

    protected $fillable = [
        'zapros_id',
        'polzovatel_id',
        'ot_kogo',
        'tekst',
        'sozdano_at',
    ];

    protected $casts = [
        'sozdano_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(PropertyInfoRequest::class, 'zapros_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }

    public function isStaff(): bool
    {
        return $this->ot_kogo === 'staff';
    }
}
