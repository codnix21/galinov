<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Роль пользователя: admin, realtor, client, guest.
 *
 * Справочник roli; у пользователя в polzovateli хранится rol_id.
 */
class Role extends Model
{
    use HasFactory;

    protected $table = 'roli';

    const CREATED_AT = 'sozdano_at';
    const UPDATED_AT = 'obnovleno_at';

    protected $fillable = [
        'kod',
        'nazvanie',
    ];

    protected $casts = [
        'sozdano_at' => 'datetime',
        'obnovleno_at' => 'datetime',
    ];

    /** Все пользователи с этой ролью */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}
