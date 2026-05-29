<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\PublicDisk;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Пользователь сайта: клиент, риелтор, администратор.
 *
 * Таблица polzovateli. Для входа используется email; роль хранится в roli по rol_id.
 * Поля name, email, role — удобные алиасы для ФИО, email_polzovatela и кода роли.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'polzovateli';

    /** Колонки даты создания и обновления (не стандартные created_at / updated_at) */
    const CREATED_AT = 'sozdano_at';
    const UPDATED_AT = 'obnovleno_at';

    protected $fillable = [
        'familia',
        'imya',
        'otchestvo',
        'email_polzovatela',
        'parol',
        'rol_id',
        'telefon',
        'pol',
        'telegram_chat_id',
        'biografiya',
        'avatar_polzovatela',
        'zablokirovan',
    ];

    public function personalData(): HasOne
    {
        return $this->hasOne(UserPersonalData::class, 'polzovatel_id');
    }

    /** Полное имя из familia + imya + otchestvo (алиас name) */
    public function getNameAttribute()
    {
        return trim(implode(' ', array_filter([
            $this->attributes['familia'] ?? null,
            $this->attributes['imya'] ?? null,
            $this->attributes['otchestvo'] ?? null,
        ]))) ?: ($this->attributes['name_old'] ?? '');
    }

    /** Email для входа и уведомлений (колонка email_polzovatela) */
    public function getEmailAttribute()
    {
        return $this->attributes['email_polzovatela'] ?? null;
    }

    /** Хэш пароля — не отдавать наружу (колонка parol) */
    public function getPasswordAttribute()
    {
        return $this->attributes['parol'] ?? null;
    }

    /** Код роли: admin, realtor, client, guest — из связи roleRelation */
    public function getRoleAttribute()
    {
        // Берём kod из справочника roli по rol_id
        if (isset($this->attributes['rol_id'])) {
            if (!$this->relationLoaded('roleRelation')) {
                $this->load('roleRelation');
            }
            if ($this->relationLoaded('roleRelation') && $this->roleRelation) {
                return $this->roleRelation->kod ?? null;
            }
        }

        return null;
    }

    /** То же, что role — для обращения $user->rol в шаблонах */
    public function getRolAttribute(): ?string
    {
        return $this->role;
    }

    /** Телефон для связи (алиас telefon) */
    public function getPhoneAttribute()
    {
        return $this->attributes['telefon'] ?? null;
    }

    /** Текст «о себе» в профиле */
    public function getBioAttribute()
    {
        return $this->attributes['biografiya'] ?? null;
    }

    /** Путь к файлу аватара в storage */
    public function getAvatarAttribute()
    {
        return $this->attributes['avatar_polzovatela'] ?? null;
    }

    /** URL аватара для тега img (маршрут /media/… или storage) */
    public function getAvatarUrlAttribute(): ?string
    {
        $path = $this->attributes['avatar_polzovatela'] ?? null;
        if ($path === null || $path === '') {
            return null;
        }

        $url = PublicDisk::publicUrl($path);

        return $url !== '' ? $url : null;
    }

    /** Когда зарегистрировался (sozdano_at) */
    public function getCreatedAtAttribute()
    {
        if (!isset($this->attributes['sozdano_at'])) {
            return null;
        }
        return $this->asDateTime($this->attributes['sozdano_at']);
    }

    /** Когда последний раз меняли профиль */
    public function getUpdatedAtAttribute()
    {
        if (!isset($this->attributes['obnovleno_at'])) {
            return null;
        }
        return $this->asDateTime($this->attributes['obnovleno_at']);
    }

    public function setNameAttribute($value)
    {
        // Строка «Иванов Иван Иванович» → familia, imya, otchestvo
        $parts = explode(' ', trim($value), 3);
        $this->attributes['familia'] = $parts[0] ?? null;
        $this->attributes['imya'] = $parts[1] ?? null;
        $this->attributes['otchestvo'] = $parts[2] ?? null;
    }

    /** Сохранение email в колонку email_polzovatela */
    public function setEmailAttribute($value)
    {
        $this->attributes['email_polzovatela'] = $value;
    }

    /** Пароль хэшируется через cast 'hashed' при сохранении */
    public function setPasswordAttribute($value)
    {
        $this->attributes['parol'] = $value;
    }

    /** При записи role ищем id роли в справочнике и сохраняем rol_id */
    public function setRoleAttribute($value)
    {
        if (in_array($value, ['admin', 'realtor', 'client', 'guest'], true)) {
            $role = Role::where('kod', $value)->first();
            if ($role) {
                $this->attributes['rol_id'] = $role->id;
            }
        }
    }

    public function setPhoneAttribute($value)
    {
        $this->attributes['telefon'] = $value;
    }

    public function setBioAttribute($value)
    {
        $this->attributes['biografiya'] = $value;
    }

    public function setAvatarAttribute($value)
    {
        $this->attributes['avatar_polzovatela'] = $value;
    }

    /** Не отдавать пароль и токен «запомнить меня» в JSON/API */
    protected $hidden = [
        'parol',
        'remember_token',
    ];

    /** Преобразование дат и хэширование пароля при записи в БД */
    protected function casts(): array
    {
        return [
            'email_podtverzhden_at' => 'datetime',
            'parol' => 'hashed',
            'sozdano_at' => 'datetime',
            'obnovleno_at' => 'datetime',
        ];
    }

    /**
     * В сессии «id» пользователя — это email, не число из таблицы.
     * Для связей с другими таблицами используйте Auth::user()->getKey().
     */
    public function getAuthIdentifierName()
    {
        return 'email_polzovatela';
    }

    /** Пароль для проверки при входе (колонка parol) */
    public function getAuthPassword()
    {
        return $this->parol;
    }
    
    /** Имя колонки «создано» для Eloquent */
    public function getCreatedAtColumn()
    {
        return 'sozdano_at';
    }
    
    /** Имя колонки «обновлено» для Eloquent */
    public function getUpdatedAtColumn()
    {
        return 'obnovleno_at';
    }

    /** Запись роли из справочника roli */
    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    /** Полный доступ к админ-панели */
    public function isAdmin(): bool
    {
        $role = $this->role;
        return $role === 'admin';
    }

    /** Может вести сделки и подтверждать договоры */
    public function isRealtor(): bool
    {
        $role = $this->role;
        return $role === 'realtor';
    }

    /** Админ или риелтор — доступ к модерации и служебным разделам */
    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isRealtor();
    }

    /** Обычный покупатель/арендатор */
    public function isClient(): bool
    {
        $role = $this->role;
        return $role === 'client';
    }

    /** Роль по умолчанию без расширенных прав */
    public function isGuest(): bool
    {
        $role = $this->role;
        return $role === 'guest';
    }

    /** Объявления, которые создал этот пользователь */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'polzovatel_id');
    }

    /** Договоры, где пользователь — покупатель (pokupatel_id) */
    public function clientContracts()
    {
        return $this->hasMany(Contract::class, 'pokupatel_id');
    }

    /** Договоры, где пользователь — риелтор (rieltor_id) */
    public function realtorContracts()
    {
        return $this->hasMany(Contract::class, 'rieltor_id');
    }

    /** Записи «избранное» этого пользователя */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'polzovatel_id');
    }

    /** Есть ли это объявление в избранном у пользователя */
    public function hasFavorite(Property $property): bool
    {
        return $this->favorites()->where('nedvizhimost_id', $property->id)->exists();
    }

    /** Админ заблокировал вход — middleware check.blocked не пустит на сайт */
    public function isBlocked(): bool
    {
        return (bool) ($this->attributes['zablokirovan'] ?? false);
    }

    /** Заблокировать вход (zablokirovan = true) */
    public function block(): void
    {
        $this->update(['zablokirovan' => true]);
    }

    /** Снять блокировку — пользователь снова может войти */
    public function unblock(): void
    {
        $this->update(['zablokirovan' => false]);
    }
}
