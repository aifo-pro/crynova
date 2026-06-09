<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Refund extends Model
{
    protected $fillable = [
        'uuid', 'merchant_id', 'invoice_id', 'currency_id',
        'amount', 'to_address', 'memo', 'type', 'status',
        'tx_hash', 'reason', 'admin_notes', 'approved_by',
        'approved_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at'  => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $refund) {
            if (empty($refund->uuid)) {
                $refund->uuid = (string) Str::uuid();
            }
        });
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PaymentInvoice::class, 'invoice_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['completed', 'rejected', 'failed']);
    }
}
