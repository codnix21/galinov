<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Объект в подборке (podborki_obekty).
 */
class CollectionProperty extends Model
{
    public $timestamps = false;

    protected $table = 'podborki_obekty';

    protected $fillable = [
        'podborka_id',
        'nedvizhimost_id',
        'poryadok',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(PropertyCollection::class, 'podborka_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'nedvizhimost_id');
    }
}
