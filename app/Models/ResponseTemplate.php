<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponseTemplate extends Model
{
    protected $table = 'shablony_otvetov';

    protected $fillable = [
        'rieltor_id',
        'kod',
        'nazvanie',
        'tekst',
        'kontekst',
    ];

    public function realtor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rieltor_id');
    }
}
