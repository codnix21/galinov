<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Продавец в договоре (снимок собственников на момент оформления). */
class ContractSeller extends Model
{
    protected $table = 'dogovory_prodavtsy';

    protected $fillable = [
        'dogovor_id',
        'polzovatel_id',
        'dolya_procent',
        'poryadok',
        'ecp_podpis_at',
        'ecp_podpis_nomera',
    ];

    protected $casts = [
        'dolya_procent' => 'decimal:2',
        'ecp_podpis_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'dogovor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'polzovatel_id');
    }

    public function fio(): string
    {
        $u = $this->relationLoaded('user') ? $this->user : $this->user()->first();

        return trim(($u->familia ?? '').' '.($u->imya ?? '').' '.($u->otchestvo ?? ''));
    }
}
