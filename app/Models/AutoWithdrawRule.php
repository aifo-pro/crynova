<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoWithdrawRule extends Model
{
    protected $fillable = [
        'merchant_id', 'currency_id', 'address', 'memo', 'min_amount', 'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:18',
            'is_enabled' => 'boolean',
        ];
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
