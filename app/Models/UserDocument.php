<?php

namespace App\Models;

use App\Support\PropertyDocumentRules;
use App\Support\PublicDisk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Документ пользователя для проверки (паспорт, выписка ЕГРН и т.д.).
 */
class UserDocument extends Model
{
    protected $table = 'dokumenty_proverki';

    protected $fillable = [
        'polzovatel_id',
        'nedvizhimost_id',
        'tip',
        'tip_obekta',
        'nazvanie',
        'put_fajla',
        'status',
        'kommentariy_mod',
        'provereno_at',
        'vneshniy_id',
        'vneshniy_status',
        'vneshniy_provereno_at',
    ];

    const CREATED_AT = 'sozdano_at';

    const UPDATED_AT = 'obnovleno_at';

    protected $casts = [
        'provereno_at' => 'datetime',
        'vneshniy_provereno_at' => 'datetime',
        'sozdano_at' => 'datetime',
        'obnovleno_at' => 'datetime',
    ];

    /** @return array<string, string> */
    public static function tipLabels(): array
    {
        return PropertyDocumentRules::allTipLabels();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'nedvizhimost_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'verified' => 'Проверен',
            'rejected' => 'Отклонён',
            'checking' => 'Автопроверка…',
            default => 'На модерации',
        };
    }

    public function getPublicUrlAttribute(): ?string
    {
        $url = PublicDisk::publicUrl($this->put_fajla);

        return $url !== '' ? $url : null;
    }

    /** Просмотр с проверкой прав (для паспорта, ЕГРН и др.). */
    public function getViewUrlAttribute(): ?string
    {
        if ($this->put_fajla === null || $this->put_fajla === '') {
            return null;
        }

        if (\App\Support\DocumentStorage::resolveRelativePath($this->put_fajla) === null) {
            return null;
        }

        return route('documents.view', $this);
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }
}
