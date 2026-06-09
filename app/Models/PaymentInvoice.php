<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PaymentInvoice extends Model
{
    protected $fillable = [
        'uuid', 'merchant_id', 'currency_id', 'order_id', 'description',
        'amount', 'amount_received', 'pay_address', 'pay_memo', 'status',
        'rate_usd', 'fee_percent', 'fee_amount', 'net_amount',
        'expires_at', 'paid_at', 'webhook_attempts', 'webhook_last_sent_at',
        'webhook_delivered', 'metadata', 'refund_address', 'refund_tx_hash',
    ];

    protected function casts(): array
    {
        return [
            'amount'               => 'decimal:18',
            'amount_received'      => 'decimal:18',
            'rate_usd'             => 'decimal:8',
            'fee_percent'          => 'decimal:2',
            'fee_amount'           => 'decimal:18',
            'net_amount'           => 'decimal:18',
            'expires_at'           => 'datetime',
            'paid_at'              => 'datetime',
            'webhook_last_sent_at' => 'datetime',
            'webhook_delivered'    => 'boolean',
            'metadata'             => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $invoice) {
            if (empty($invoice->uuid)) {
                $invoice->uuid = Str::uuid()->toString();
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

    public function transactions(): HasMany
    {
        return $this->hasMany(BlockchainTransaction::class, 'invoice_id');
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(WebhookLog::class, 'invoice_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expires_at && $this->expires_at->isPast() && in_array($this->status, ['pending', 'waiting_confirmations'], true));
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['paid', 'underpaid', 'overpaid', 'expired', 'failed', 'refunded'], true);
    }
}
