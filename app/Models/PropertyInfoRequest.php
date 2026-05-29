<?php

namespace App\Models;

use App\Models\Concerns\MapsRequestStatus;
use App\Support\PropertyInfoRequestTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Запрос доп. информации по объекту (zaprosy_dop_informacii). */
class PropertyInfoRequest extends Model
{
    use MapsRequestStatus;

    protected $table = 'zaprosy_dop_informacii';

    protected $fillable = [
        'nedvizhimost_id',
        'polzovatel_id',
        'tip',
        'status_zayavki_id',
    ];

    public static function statusGruppa(): string
    {
        return 'info';
    }

    const CREATED_AT = 'sozdano_at';

    const UPDATED_AT = 'obnovleno_at';

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'nedvizhimost_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PropertyInfoRequestMessage::class, 'zapros_id')->orderBy('sozdano_at');
    }

    public function tipLabel(): string
    {
        return PropertyInfoRequestTypes::label($this->tip);
    }

    public function statusLabel(): string
    {
        return RequestStatus::nazvanieFor('info', $this->status_zayavki_id)
            ?? match ($this->status) {
                'answered' => 'Получен ответ',
                'closed' => 'Закрыт',
                default => 'Ожидает ответа',
            };
    }
}
