<?php

namespace App\Models;

use App\Models\Concerns\MapsRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Заявка клиента на подбор по критериям каталога (zayavki_podbora). */
class PropertySelectionRequest extends Model
{
    use MapsRequestStatus;

    protected $table = 'zayavki_podbora';

    protected $fillable = [
        'polzovatel_id',
        'imya',
        'telefon',
        'email',
        'kommentariy',
        'filtry',
        'status_zayavki_id',
        'istochnik',
    ];

    public static function statusGruppa(): string
    {
        return 'selection';
    }

    protected $casts = [
        'filtry' => 'array',
    ];

    const CREATED_AT = 'sozdano_at';

    const UPDATED_AT = 'obnovleno_at';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }

    /** Краткое описание критериев для списка риэлтора. */
    public function filtersSummary(): string
    {
        $f = $this->filtry ?? [];
        if ($f === []) {
            return '—';
        }

        $parts = [];
        if (!empty($f['type'])) {
            $parts[] = Property::nazvanieTipa($f['type']);
        }
        if (!empty($f['operation'])) {
            $parts[] = $f['operation'] === 'rent' ? 'аренда' : 'продажа';
        }
        if (!empty($f['city_id'])) {
            $city = \App\Models\City::find((int) $f['city_id']);
            if ($city) {
                $parts[] = $city->nazvanie;
            }
        }
        if (!empty($f['min_price']) || !empty($f['max_price'])) {
            $parts[] = 'цена '.($f['min_price'] ?? '…').'–'.($f['max_price'] ?? '…');
        }
        if (!empty($f['search'])) {
            $parts[] = 'поиск: '.$f['search'];
        }

        return $parts !== [] ? implode(', ', $parts) : json_encode($f, JSON_UNESCAPED_UNICODE);
    }
}
