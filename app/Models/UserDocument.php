<?php

namespace App\Models;

use App\Support\PropertyDocumentRules;
use App\Support\PublicDisk;
use Illuminate\Database\Eloquent\Builder;
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
        'status_dokumenta_id',
        'status',
        'kommentariy_mod',
        'dannye_json',
        'provereno_at',
        'vneshniy_id',
        'vneshniy_status',
        'vneshniy_provereno_at',
    ];

    const CREATED_AT = 'sozdano_at';

    const UPDATED_AT = 'obnovleno_at';

    protected $casts = [
        'dannye_json' => 'array',
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

    public function statusRelation(): BelongsTo
    {
        return $this->belongsTo(DocumentStatus::class, 'status_dokumenta_id');
    }

    /** Код статуса (pending, verified…) — виртуально, в БД только status_dokumenta_id. */
    public function getStatusAttribute(): string
    {
        return DocumentStatus::kodFor(isset($this->attributes['status_dokumenta_id'])
            ? (int) $this->attributes['status_dokumenta_id']
            : null) ?? 'pending';
    }

    public function setStatusAttribute(?string $value): void
    {
        $id = DocumentStatus::idFor($value ?: 'pending') ?? DocumentStatus::idFor('pending');
        if ($id) {
            $this->attributes['status_dokumenta_id'] = $id;
        }
    }

    /** @param  Builder<UserDocument>  $query */
    public function scopeWhereStatusKod(Builder $query, string $kod): Builder
    {
        $id = DocumentStatus::idFor($kod);

        return $id ? $query->where('status_dokumenta_id', $id) : $query->whereRaw('1 = 0');
    }

    /** @param  Builder<UserDocument>  $query  @param  list<string>  $kody */
    public function scopeWhereStatusKodIn(Builder $query, array $kody): Builder
    {
        $ids = array_values(array_filter(array_map(fn (string $k) => DocumentStatus::idFor($k), $kody)));

        return $ids !== [] ? $query->whereIn('status_dokumenta_id', $ids) : $query->whereRaw('1 = 0');
    }

    public function statusLabel(): string
    {
        return $this->statusRelation?->nazvanie ?? match ($this->status) {
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

    /** @return list<array{label: string, value: string}> */
    public function dataDisplayLines(): array
    {
        return \App\Support\DocumentDataFields::displayLines(
            (string) $this->tip,
            $this->dannye_json,
            $this->property,
        );
    }
}
