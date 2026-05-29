<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Избранное: связь «пользователь сохранил объявление».
 *
 * Таблица izbrannoe — пара polzovatel_id + nedvizhimost_id.
 */
class Favorite extends Model
{
    use HasFactory;

    protected $table = 'izbrannoe';

    protected $fillable = [
        'polzovatel_id',
        'nedvizhimost_id',
    ];

    /** user_id и property_id — алиасы для polzovatel_id и nedvizhimost_id */

    /** Кто сохранил объявление */
    public function getUserIdAttribute()
    {
        return $this->attributes['polzovatel_id'] ?? null;
    }

    /** Какое объявление в избранном */
    public function getPropertyIdAttribute()
    {
        return $this->attributes['nedvizhimost_id'] ?? null;
    }

    public function setUserIdAttribute($value)
    {
        $this->attributes['polzovatel_id'] = $value;
    }

    public function setPropertyIdAttribute($value)
    {
        $this->attributes['nedvizhimost_id'] = $value;
    }

    protected $casts = [
        'sozdano_at' => 'datetime',
        'obnovleno_at' => 'datetime',
    ];
    
    const CREATED_AT = 'sozdano_at';
    const UPDATED_AT = 'obnovleno_at';

    /** Кто добавил в избранное */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }

    /** Какое объявление в избранном */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'nedvizhimost_id');
    }
}
