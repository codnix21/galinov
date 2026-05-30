<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentPayment extends Model
{
    protected $table = 'arenda_platezhi';

    protected $fillable = [
        'dogovor_id',
        'data_platezha',
        'summa',
        'status',
        'oplacheno_at',
        'poryadok',
    ];

    protected $casts = [
        'data_platezha' => 'date',
        'summa' => 'decimal:2',
        'oplacheno_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'dogovor_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
