<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\PublicDisk;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Договор по объекту недвижимости (продажа или аренда).
 *
 * Таблица dogovory. Стороны: владелец объекта, покупатель, риэлтор сделки.
 * В коде можно использовать property_id, status — они мапятся на колонки БД.
 */
class Contract extends Model
{
    use HasFactory;

    protected $table = 'dogovory';

    protected $fillable = [
        'nedvizhimost_id',
        'vladelets_id',
        'pokupatel_id',
        'klient_id',
        'rieltor_id',
        'sozdal_kak',
        'sozdal_storona',
        'podtverzhden_vladelets_at',
        'podtverzhden_pokupatel_at',
        'podtverzhden_rieltor_at',
        'tip',
        'tsena',
        'data_nachala',
        'data_okonchaniya',
        'status_dogovora',
        'status_dogovora_id',
        'primechaniya',
        'skan_dogovora',
        'ozhidaet_podtverzhdeniya',
        'oplata_status',
        'oplata_at',
        'oplata_metod',
        'oplata_tranzaktsiya',
        'oplata_summa',
        'avto_zapolnen',
        'ecp_podpis_vladelets_at',
        'ecp_podpis_vladelets_nomera',
        'ecp_podpis_vladelets_fio',
        'ecp_podpis_pokupatel_at',
        'ecp_podpis_pokupatel_nomera',
        'ecp_podpis_pokupatel_fio',
        'ecp_podpis_rieltor_at',
        'ecp_podpis_rieltor_nomera',
        'ecp_podpis_rieltor_fio',
    ];

    /** При удалении договора убираем файл скана с диска public */
    protected static function booted(): void
    {
        static::saving(function (Contract $contract): void {
            if ($contract->pokupatel_id) {
                $contract->klient_id = $contract->pokupatel_id;
            }
        });

        static::deleting(function (Contract $contract): void {
            if (!empty($contract->skan_dogovora)) {
                Storage::disk('public')->delete($contract->skan_dogovora);
            }
        });
    }

    /** Ссылка на скачанный PDF/скан для скачивания или просмотра */
    public function getSkanDogovoraUrlAttribute(): ?string
    {
        if (empty($this->skan_dogovora)) {
            return null;
        }

        $url = PublicDisk::publicUrl($this->skan_dogovora);

        return $url !== '' ? $url : null;
    }

    /** Алиасы английских имён полей (property_id, client_id, status…) */

    /** Id объявления (nedvizhimost_id) */
    public function getPropertyIdAttribute()
    {
        return $this->attributes['nedvizhimost_id'] ?? null;
    }

    /** Id клиента по договору */
    public function getClientIdAttribute()
    {
        return $this->attributes['klient_id'] ?? null;
    }

    /** Id риелтора по договору */
    public function getRealtorIdAttribute()
    {
        return $this->attributes['rieltor_id'] ?? null;
    }

    /** sale или rent — должен совпадать с типом объявления */
    public function getTypeAttribute()
    {
        return $this->attributes['tip'] ?? null;
    }

    /** Сумма сделки или месячная аренда */
    public function getPriceAttribute()
    {
        return $this->attributes['tsena'] ?? null;
    }

    /** Дата начала действия договора */
    public function getStartDateAttribute()
    {
        return $this->attributes['data_nachala'] ?? null;
    }

    /** Дата окончания — обязательна для аренды */
    public function getEndDateAttribute()
    {
        return $this->attributes['data_okonchaniya'] ?? null;
    }

    /** Код статуса: draft, pending, active, completed, cancelled */
    public function getStatusAttribute()
    {
        return ContractStatus::kodFor(isset($this->attributes['status_dogovora_id'])
            ? (int) $this->attributes['status_dogovora_id']
            : null);
    }

    /** Для форм с полем status_dogovora — то же, что status */
    public function getStatusDogovoraAttribute(): ?string
    {
        return $this->status;
    }

    public function setStatusDogovoraAttribute($value): void
    {
        $this->setStatusAttribute($value);
    }

    /** Свободный текст примечаний к договору */
    public function getNotesAttribute()
    {
        return $this->attributes['primechaniya'] ?? null;
    }

    public function setPropertyIdAttribute($value)
    {
        $this->attributes['nedvizhimost_id'] = $value;
    }

    public function setClientIdAttribute($value)
    {
        $this->attributes['klient_id'] = $value;
    }

    public function setRealtorIdAttribute($value)
    {
        $this->attributes['rieltor_id'] = $value;
    }

    public function setTypeAttribute($value)
    {
        $this->attributes['tip'] = $value;
    }

    public function setPriceAttribute($value)
    {
        $this->attributes['tsena'] = $value;
    }

    public function setStartDateAttribute($value)
    {
        $this->attributes['data_nachala'] = $value;
    }

    public function setEndDateAttribute($value)
    {
        $this->attributes['data_okonchaniya'] = $value;
    }

    /** Записывает status_dogovora_id по коду или числовому id */
    public function setStatusAttribute($value)
    {
        if (in_array($value, ['draft', 'pending', 'active', 'completed', 'cancelled'], true)) {
            $id = ContractStatus::idFor($value);
            if ($id !== null) {
                $this->attributes['status_dogovora_id'] = $id;
            }
        } elseif (is_numeric($value)) {
            $this->attributes['status_dogovora_id'] = $value;
        }
    }

    public function setNotesAttribute($value)
    {
        $this->attributes['primechaniya'] = $value;
    }

    public function getCreatedAtAttribute()
    {
        if (!isset($this->attributes['sozdano_at'])) {
            return null;
        }
        return $this->asDateTime($this->attributes['sozdano_at']);
    }

    public function getUpdatedAtAttribute()
    {
        if (!isset($this->attributes['obnovleno_at'])) {
            return null;
        }
        return $this->asDateTime($this->attributes['obnovleno_at']);
    }

    /** Типы полей при чтении из БД */
    protected $casts = [
        'tsena' => 'decimal:2',
        'data_nachala' => 'date',
        'data_okonchaniya' => 'date',
        'oplata_at' => 'datetime',
        'oplata_summa' => 'decimal:2',
        'avto_zapolnen' => 'boolean',
        'ecp_podpis_vladelets_at' => 'datetime',
        'ecp_podpis_pokupatel_at' => 'datetime',
        'ecp_podpis_rieltor_at' => 'datetime',
        'podtverzhden_vladelets_at' => 'datetime',
        'podtverzhden_pokupatel_at' => 'datetime',
        'podtverzhden_rieltor_at' => 'datetime',
        'sozdano_at' => 'datetime',
        'obnovleno_at' => 'datetime',
    ];
    
    const CREATED_AT = 'sozdano_at';
    const UPDATED_AT = 'obnovleno_at';

    public function isPaid(): bool
    {
        return in_array($this->oplata_status, ['simulated_paid', 'robokassa_paid'], true);
    }

    /** Объявление, к которому относится договор */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'nedvizhimost_id');
    }

    /** Сторона 1: владелец объекта */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vladelets_id');
    }

    /** Сторона 2: покупатель */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pokupatel_id');
    }

    /** Покупатель (алиас; для старых записей — klient_id) */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'klient_id');
    }

    /** Покупатель с учётом legacy-поля klient_id */
    public function resolvedBuyer(): ?User
    {
        if ($this->pokupatel_id) {
            return $this->buyer;
        }

        return $this->klient_id ? $this->client : null;
    }

    /** Владелец с подстановкой из объявления при отсутствии vladelets_id */
    public function resolvedOwner(): ?User
    {
        if ($this->vladelets_id) {
            return $this->owner;
        }

        $this->loadMissing('property.user');

        return $this->property?->user;
    }

    /** Риелтор, ведущий сделку */
    public function realtor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rieltor_id');
    }

    /** Статус из справочника statusy_dogovorov */
    public function statusRelation(): BelongsTo
    {
        return $this->belongsTo(ContractStatus::class, 'status_dogovora_id');
    }

    /** Тип сделки по-русски (продажа / аренда) */
    public function getTypeNameAttribute(): string
    {
        $type = $this->attributes['tip'] ?? $this->type;
        return match($type) {
            'sale' => 'Продажа',
            'rent' => 'Аренда',
            default => $type ?? 'Неизвестно',
        };
    }

    /** Статус договора по-русски для интерфейса */
    public function getStatusNameAttribute(): string
    {
        // Если statusRelation загружена — nazvanie из справочника
        if (isset($this->attributes['status_dogovora_id']) && $this->relationLoaded('statusRelation')) {
            return $this->statusRelation->nazvanie ?? 'Неизвестно';
        }
        // Иначе используем match для старого поля
        $status = $this->status;
        return match($status) {
            'draft' => 'Черновик',
            'pending' => 'Ожидает подтверждения',
            'active' => 'Активен',
            'completed' => 'Завершен',
            'cancelled' => 'Отменен',
            default => $status ?? 'Неизвестно',
        };
    }
}
