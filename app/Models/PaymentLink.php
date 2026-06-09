<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PaymentLink extends Model
{
    protected $fillable = [
        'merchant_id', 'token', 'title', 'description',
        'amount', 'currency_id', 'order_id_prefix',
        'success_url', 'max_uses', 'use_count', 'is_active', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount'    => 'decimal:18',
            'is_active' => 'boolean',
            'metadata'  => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $link) {
            if (empty($link->token)) {
                $link->token = Str::random(16);
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

    public function invoices(): HasMany
    {
        return $this->hasMany(PaymentInvoice::class, 'payment_link_id');
    }

    /** Whether this link can still accept payments. */
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->max_uses !== null && $this->use_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function getPublicUrl(): string
    {
        return route('checkout.link', $this->token);
    }
}
