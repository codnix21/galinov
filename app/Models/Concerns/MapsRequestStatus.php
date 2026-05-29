<?php

namespace App\Models\Concerns;

use App\Models\RequestStatus;
use Illuminate\Database\Eloquent\Builder;

/** Виртуальное поле status ↔ status_zayavki_id (3НФ). */
trait MapsRequestStatus
{
    abstract public static function statusGruppa(): string;

    public function initializeMapsRequestStatus(): void
    {
        if (!in_array('status', $this->fillable, true)) {
            $this->fillable[] = 'status';
        }
    }

    public function getStatusAttribute(): ?string
    {
        return RequestStatus::kodFor(static::statusGruppa(), isset($this->attributes['status_zayavki_id'])
            ? (int) $this->attributes['status_zayavki_id']
            : null) ?? 'new';
    }

    public function setStatusAttribute(?string $value): void
    {
        $gruppa = static::statusGruppa();
        $id = ($value !== null && $value !== '')
            ? (RequestStatus::idFor($gruppa, $value) ?? RequestStatus::idFor($gruppa, 'new'))
            : RequestStatus::idFor($gruppa, 'new');
        if ($id) {
            $this->attributes['status_zayavki_id'] = $id;
        }
    }

    /** @param  Builder<static>  $query */
    public function scopeWhereStatusKod(Builder $query, string $kod): Builder
    {
        $id = RequestStatus::idFor(static::statusGruppa(), $kod);

        return $id ? $query->where('status_zayavki_id', $id) : $query->whereRaw('1 = 0');
    }
}
