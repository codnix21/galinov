<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Объявление о недвижимости (квартира, дом и т.д.).
 *
 * Таблица в БД — nedvizhimost. В коде можно писать title, price, city —
 * модель сама переводит это в поля nazvanie, tsena, gorod_id.
 */
class Property extends Model
{
    use HasFactory;

    /** Имя таблицы в базе данных */
    protected $table = 'nedvizhimost';

    /** Поля, которые можно массово заполнять (create/update) */
    protected $fillable = [
        'nazvanie',
        'opisanie',
        'tip',
        'operatsiya',
        'tsena',
        'gorod_id',
        'adres_ulitsy',
        'kadastrovy_nomer',
        'geo_shirota',
        'geo_dolgota',
        'ploshchad',
        'komnaty',
        'etazh',
        'vsego_etazhey',
        'polzovatel_id',
        'rieltor_id',
        'sozdal_kak',
        'status_obyavleniya',
        'status_obyavleniya_id',
        'prichina_otkaza_mod',
        'tip_doma',
        'est_tsokol',
        'ploshchad_uchastka',
        'garazh',
        'parking',
        'internet',
        'otoplenie',
        'kanalizatsiya',
        'vodosnabzhenie',
        'gaz',
        'banya',
        'bassein',
        'okhrana',
        'zabor',
        'tip_pomeshcheniya',
        'vysota_potolkov',
        'otdelnyy_vhod',
    ];

    public function isHouse(): bool
    {
        return ($this->tip ?? $this->attributes['tip'] ?? null) === 'house';
    }

    /**
     * Ниже — «геттеры» и «сеттеры» для английских имён полей (title, price…).
     * Старый код и шаблоны могут использовать их вместо nazvanie, tsena и т.д.
     */

    /** Название объявления (алиас для nazvanie) */
    public function getTitleAttribute()
    {
        return $this->attributes['nazvanie'] ?? null;
    }

    /** Текст описания (алиас opisanie) */
    public function getDescriptionAttribute()
    {
        return $this->attributes['opisanie'] ?? null;
    }

    /** Тип недвижимости: apartment, house, commercial, land */
    public function getTypeAttribute()
    {
        return $this->attributes['tip'] ?? null;
    }

    /** Операция: sale — продажа, rent — аренда */
    public function getOperationAttribute()
    {
        return $this->attributes['operatsiya'] ?? null;
    }

    /** Цена в рублях (алиас tsena) */
    public function getPriceAttribute()
    {
        return $this->attributes['tsena'] ?? null;
    }

    /** Название города по gorod_id (подгружает связь или кэш справочника) */
    public function getCityAttribute()
    {
        if (!empty($this->attributes['gorod_id'])) {
            if ($this->relationLoaded('cityRelation') && $this->cityRelation) {
                return $this->cityRelation->nazvanie;
            }

            return City::nazvanieFor((int) $this->attributes['gorod_id']);
        }

        return null;
    }

    /** То же, что city — для шаблонов и кода с $property->gorod (колонки gorod в БД нет) */
    public function getGorodAttribute(): ?string
    {
        return $this->city;
    }

    /** Улица и дом без названия города (алиас adres_ulitsy) */
    public function getStreetAddressAttribute()
    {
        return $this->attributes['adres_ulitsy'] ?? null;
    }

    /** Площадь, м² */
    public function getAreaAttribute()
    {
        return $this->attributes['ploshchad'] ?? null;
    }

    /** Количество комнат */
    public function getRoomsAttribute()
    {
        return $this->attributes['komnaty'] ?? null;
    }

    /** Этаж квартиры */
    public function getFloorAttribute()
    {
        return $this->attributes['etazh'] ?? null;
    }

    /** Этажность здания */
    public function getTotalFloorsAttribute()
    {
        return $this->attributes['vsego_etazhey'] ?? null;
    }

    /** Id владельца объявления (алиас polzovatel_id) */
    public function getUserIdAttribute()
    {
        return $this->attributes['polzovatel_id'] ?? null;
    }

    /** Код статуса: draft, active, sold… (из справочника statusy_obyavleniy) */
    public function getStatusAttribute()
    {
        return PropertyStatus::kodFor(isset($this->attributes['status_obyavleniya_id'])
            ? (int) $this->attributes['status_obyavleniya_id']
            : null);
    }

    /** То же, что status — для полей формы с именем status_obyavleniya */
    public function getStatusObyavleniyaAttribute(): ?string
    {
        return $this->status;
    }

    /** Запись статуса через имя поля формы status_obyavleniya */
    public function setStatusObyavleniyaAttribute($value): void
    {
        $this->setStatusAttribute($value);
    }

    public function setTitleAttribute($value)
    {
        $this->attributes['nazvanie'] = $value;
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['opisanie'] = $value;
    }

    public function setTypeAttribute($value)
    {
        $this->attributes['tip'] = $value;
    }

    public function setOperationAttribute($value)
    {
        $this->attributes['operatsiya'] = $value;
    }

    public function setPriceAttribute($value)
    {
        $this->attributes['tsena'] = $value;
    }

    /** При записи city: строка — найти/создать город, число — записать gorod_id */
    public function setCityAttribute($value)
    {
        // Если значение — название города (строка), а не id
        if (is_string($value) && !is_numeric($value)) {
            $city = City::firstOrCreate(['nazvanie' => $value]);
            $this->attributes['gorod_id'] = $city->id;
        } elseif (is_numeric($value)) {
            $this->attributes['gorod_id'] = $value;
        }
    }

    /** Запись города из поля формы gorod (алиас setCityAttribute) */
    public function setGorodAttribute($value): void
    {
        $this->setCityAttribute($value);
    }

    public function setStreetAddressAttribute($value)
    {
        $this->attributes['adres_ulitsy'] = $value;
    }

    public function setAreaAttribute($value)
    {
        $this->attributes['ploshchad'] = $value;
    }

    public function setRoomsAttribute($value)
    {
        $this->attributes['komnaty'] = $value;
    }

    public function setFloorAttribute($value)
    {
        $this->attributes['etazh'] = $value;
    }

    public function setTotalFloorsAttribute($value)
    {
        $this->attributes['vsego_etazhey'] = $value;
    }

    /** Присвоение владельца из user_id в форме */
    public function setUserIdAttribute($value)
    {
        $this->attributes['polzovatel_id'] = $value;
    }

    /** Сохраняет статус по коду (active…) или по числовому id из справочника */
    public function setStatusAttribute($value)
    {
        if (in_array($value, ['draft', 'active', 'sold', 'rented', 'inactive', 'pending_review'], true)) {
            $id = PropertyStatus::idFor($value);
            if ($id !== null) {
                $this->attributes['status_obyavleniya_id'] = $id;
            }
        } elseif (is_numeric($value)) {
            $this->attributes['status_obyavleniya_id'] = $value;
        }
    }

    /** Дата создания записи (колонка sozdano_at вместо created_at) */
    public function getCreatedAtAttribute()
    {
        if (!isset($this->attributes['sozdano_at'])) {
            return null;
        }
        return $this->asDateTime($this->attributes['sozdano_at']);
    }

    /** Дата последнего изменения (obnovleno_at) */
    public function getUpdatedAtAttribute()
    {
        if (!isset($this->attributes['obnovleno_at'])) {
            return null;
        }
        return $this->asDateTime($this->attributes['obnovleno_at']);
    }

    /** Преобразование типов при чтении из БД */
    protected $casts = [
        'tsena' => 'decimal:2',
        'ploshchad_uchastka' => 'decimal:2',
        'geo_shirota' => 'float',
        'geo_dolgota' => 'float',
        'sozdano_at' => 'datetime',
        'obnovleno_at' => 'datetime',
        'est_tsokol' => 'boolean',
        'garazh' => 'boolean',
        'parking' => 'boolean',
        'internet' => 'boolean',
        'otoplenie' => 'boolean',
        'kanalizatsiya' => 'boolean',
        'vodosnabzhenie' => 'boolean',
        'gaz' => 'boolean',
        'banya' => 'boolean',
        'bassein' => 'boolean',
        'okhrana' => 'boolean',
        'zabor' => 'boolean',
    ];
    
    /** Имена колонок «создано» и «обновлено» вместо created_at / updated_at */
    const CREATED_AT = 'sozdano_at';
    const UPDATED_AT = 'obnovleno_at';

    /** Владелец объявления (клиент или риэлтор при личном/агентском размещении) */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }

    /** Риэлтор, разместивший объявление (если не клиент сам) */
    public function realtor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rieltor_id');
    }

    /** Собственники с долями в праве */
    public function owners(): HasMany
    {
        return $this->hasMany(PropertyOwner::class, 'nedvizhimost_id')->orderBy('poryadok');
    }

    /** Город из справочника goroda (по gorod_id) */
    public function cityRelation(): BelongsTo
    {
        return $this->belongsTo(City::class, 'gorod_id');
    }

    /** Запись статуса из справочника statusy_obyavleniy */
    public function statusRelation(): BelongsTo
    {
        return $this->belongsTo(PropertyStatus::class, 'status_obyavleniya_id');
    }

    /** @return array<string, string> Код типа → подпись для интерфейса (без изменений в БД) */
    public static function tipNazvaniya(): array
    {
        return [
            'apartment' => 'Квартира',
            'house' => 'Дом',
            'commercial' => 'Коммерческая недвижимость',
            'land' => 'Земельный участок',
        ];
    }

    public static function nazvanieTipa(?string $tip): string
    {
        if ($tip === null || $tip === '') {
            return 'Неизвестно';
        }

        return self::tipNazvaniya()[$tip] ?? $tip;
    }

    /** Тип недвижимости по-русски для отображения в интерфейсе */
    public function getTypeNameAttribute(): string
    {
        $type = $this->attributes['tip'] ?? $this->type;

        return self::nazvanieTipa($type);
    }

    /** Продажа или аренда — по-русски */
    public function getOperationNameAttribute(): string
    {
        $operation = $this->attributes['operatsiya'] ?? $this->operation;
        return match($operation) {
            'sale' => 'Продажа',
            'rent' => 'Аренда',
            default => $operation ?? 'Неизвестно',
        };
    }

    /** Статус объявления по-русски (из связи или по коду) */
    public function getStatusNameAttribute(): string
    {
        // Если связь statusRelation уже загружена — берём nazvanie оттуда
        if (isset($this->attributes['status_obyavleniya_id']) && $this->relationLoaded('statusRelation')) {
            return $this->statusRelation->nazvanie ?? 'Неизвестно';
        }
        // Иначе переводим код статуса вручную
        $status = $this->status;
        return match($status) {
            'draft' => 'Черновик',
            'active' => 'Активно',
            'pending_review' => 'На модерации',
            'sold' => 'Продано',
            'rented' => 'Сдано',
            'inactive' => 'Неактивно',
            default => $status ?? 'Неизвестно',
        };
    }

    /** Все договоры по этому объекту */
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'nedvizhimost_id');
    }

    /** Документы на право собственности (ЕГРН и т.д.) */
    public function ownershipDocuments()
    {
        return $this->hasMany(UserDocument::class, 'nedvizhimost_id');
    }

    /** Фотографии объявления, отсортированные по полю poryadok */
    public function images()
    {
        return $this->hasMany(PropertyImage::class, 'nedvizhimost_id')->orderBy('poryadok');
    }

    /** Кто добавил объявление в избранное */
    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'nedvizhimost_id');
    }
}


