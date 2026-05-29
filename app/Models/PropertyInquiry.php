<?php

namespace App\Models;

use App\Models\Concerns\MapsRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Заявка клиента по объекту (Lean: фиксация спроса). */
class PropertyInquiry extends Model
{
    use MapsRequestStatus;

    protected $table = 'zayavki_obekta';

    protected $fillable = [
        'nedvizhimost_id',
        'polzovatel_id',
        'imya',
        'telefon',
        'email',
        'kommentariy',
        'status_zayavki_id',
        'status',
    ];

    public static function statusGruppa(): string
    {
        return 'inquiry';
    }

    const CREATED_AT = 'sozdano_at';

    const UPDATED_AT = 'obnovleno_at';

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'nedvizhimost_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }
}
