<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractReview extends Model
{
    protected $table = 'otzyvy_sdelok';

    protected $fillable = [
        'dogovor_id',
        'polzovatel_id',
        'ocenka',
        'tekst',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'dogovor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }
}
