<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BalanceMovement extends Model
{
    protected $fillable = [
        'merchant_id', 'currency_id', 'movable_id', 'movable_type',
        'type', 'amount', 'balance_before', 'balance_after', 'note',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:18',
            'balance_before' => 'decimal:18',
            'balance_after'  => 'decimal:18',
        ];
    }

    public function movable(): MorphTo
    {
        return $this->morphTo();
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
