<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Персональные данные клиента (шифруются через casts: encrypted).
 */
class UserPersonalData extends Model
{
    protected $table = 'personalnye_dannye';

    protected $fillable = [
        'polzovatel_id',
        'pasport_seriya_nomer',
        'pasport_kem_vydan',
        'pasport_data_vydachi',
        'inn',
        'snils',
    ];

    protected $casts = [
        'pasport_seriya_nomer' => 'encrypted',
        'pasport_kem_vydan' => 'encrypted',
        'pasport_data_vydachi' => 'date',
        'inn' => 'encrypted',
        'snils' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }
}

