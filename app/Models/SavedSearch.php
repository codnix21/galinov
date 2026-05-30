<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    protected $table = 'sohranennye_poiski';

    protected $fillable = [
        'polzovatel_id',
        'nazvanie',
        'filtry',
        'uvedomleniya',
        'poslednyaya_proverka_at',
    ];

    protected $casts = [
        'filtry' => 'array',
        'uvedomleniya' => 'boolean',
        'poslednyaya_proverka_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }
}
