<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    protected $fillable = [
        'currency_id', 'invoice_id', 'address', 'memo',
        'hd_path', 'type', 'balance', 'balance_unconfirmed',
        'is_used', 'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'balance'             => 'decimal:18',
            'balance_unconfirmed' => 'decimal:18',
            'is_used'             => 'boolean',
            'last_checked_at'     => 'datetime',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PaymentInvoice::class, 'invoice_id');
    }

    public function isHot(): bool
    {
        return $this->type === 'hot';
    }
}
