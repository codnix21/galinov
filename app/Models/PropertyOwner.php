<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Собственник объекта с долей в праве (sobstvenniki_nedvizhimosti). */
class PropertyOwner extends Model
{
    protected $table = 'sobstvenniki_nedvizhimosti';

    protected $fillable = [
        'nedvizhimost_id',
        'polzovatel_id',
        'dolya_procent',
        'osnovnoy',
        'poryadok',
    ];

    protected $casts = [
        'dolya_procent' => 'decimal:2',
        'osnovnoy' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'nedvizhimost_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }

    public function fio(): string
    {
        $u = $this->relationLoaded('user') ? $this->user : $this->user()->first();

        return trim(($u->familia ?? '').' '.($u->imya ?? '').' '.($u->otchestvo ?? ''));
    }
}
