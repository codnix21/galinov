<?php

namespace App\Models;

use App\Support\PublicDisk;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Фотография объявления.
 *
 * Таблица izobrazheniya_nedvizhimosti; путь к файлу в put_k_izobrazheniyu, порядок — poryadok.
 */
class PropertyImage extends Model
{
    use HasFactory;

    protected $table = 'izobrazheniya_nedvizhimosti';

    protected $fillable = [
        'nedvizhimost_id',
        'put_k_izobrazheniyu',
        'poryadok',
    ];

    /** property_id, image_path, order — алиасы полей БД */

    /** К какому объявлению относится фото */
    public function getPropertyIdAttribute()
    {
        return $this->attributes['nedvizhimost_id'] ?? null;
    }

    /** Относительный путь в storage/app/public */
    public function getImagePathAttribute()
    {
        return $this->attributes['put_k_izobrazheniyu'] ?? null;
    }

    /** Порядок в галерее (0 — первое фото) */
    public function getOrderAttribute()
    {
        return $this->attributes['poryadok'] ?? null;
    }

    /** Готовая ссылка для тега img (учитывает диск public и путь в storage) */
    public function getPublicUrlAttribute(): string
    {
        return PublicDisk::publicUrl($this->attributes['put_k_izobrazheniyu'] ?? null);
    }

    public function setPropertyIdAttribute($value)
    {
        $this->attributes['nedvizhimost_id'] = $value;
    }

    public function setImagePathAttribute($value)
    {
        $this->attributes['put_k_izobrazheniyu'] = $value;
    }

    public function setOrderAttribute($value)
    {
        $this->attributes['poryadok'] = $value;
    }

    protected $casts = [
        'sozdano_at' => 'datetime',
        'obnovleno_at' => 'datetime',
    ];
    
    const CREATED_AT = 'sozdano_at';
    const UPDATED_AT = 'obnovleno_at';

    /** Объявление, к которому относится фото */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'nedvizhimost_id');
    }
}



