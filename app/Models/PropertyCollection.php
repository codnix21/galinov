<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Подборка объектов для клиента (podborki).
 */
class PropertyCollection extends Model
{
    protected $table = 'podborki';

    protected $fillable = [
        'rieltor_id',
        'klient_id',
        'nazvanie',
        'token',
        'kommentariy',
        'aktivna',
    ];

    const CREATED_AT = 'sozdano_at';

    const UPDATED_AT = 'obnovleno_at';

    protected $casts = [
        'aktivna' => 'boolean',
        'sozdano_at' => 'datetime',
        'obnovleno_at' => 'datetime',
    ];

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    public function publicUrl(): string
    {
        return route('collections.public', $this->token);
    }

    public function realtor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rieltor_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'klient_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CollectionProperty::class, 'podborka_id')->orderBy('poryadok');
    }

    public function properties()
    {
        return $this->hasManyThrough(
            Property::class,
            CollectionProperty::class,
            'podborka_id',
            'id',
            'id',
            'nedvizhimost_id'
        );
    }
}
