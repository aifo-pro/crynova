<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Balance extends Model
{
    protected $fillable = ['merchant_id', 'currency_id', 'available', 'locked'];

    protected function casts(): array
    {
        return [
            'available' => 'decimal:18',
            'locked'    => 'decimal:18',
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

    public function movements(): HasMany
    {
        return $this->hasMany(BalanceMovement::class, 'merchant_id', 'merchant_id')
            ->where('currency_id', $this->currency_id);
    }
}
