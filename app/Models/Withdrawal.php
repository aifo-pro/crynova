<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Withdrawal extends Model
{
    protected $fillable = [
        'uuid', 'merchant_id', 'currency_id', 'amount', 'fee',
        'amount_sent', 'to_address', 'memo', 'status',
        'tx_hash', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:18',
            'fee'         => 'decimal:18',
            'amount_sent' => 'decimal:18',
            'approved_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $w) {
            if (empty($w->uuid)) {
                $w->uuid = Str::uuid()->toString();
            }
        });
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
