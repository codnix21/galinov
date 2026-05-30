<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    protected $table = 'shablony_dogovorov';

    protected $fillable = [
        'kod',
        'nazvanie',
        'tip_dogovora',
        'vvedenie',
        'predmet',
        'obyazannosti',
        'zaklyuchenie',
        'aktiven',
    ];

    protected $casts = [
        'aktiven' => 'boolean',
    ];

    public static function activeForTip(string $tip): ?self
    {
        return self::query()
            ->where('aktiven', true)
            ->where('tip_dogovora', $tip)
            ->orderBy('id')
            ->first();
    }
}
