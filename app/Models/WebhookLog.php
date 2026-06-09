<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    protected $fillable = [
        'invoice_id', 'merchant_id', 'event', 'url', 'payload',
        'http_status', 'response_body', 'success', 'attempt', 'next_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'       => 'array',
            'success'       => 'boolean',
            'http_status'   => 'integer',
            'attempt'       => 'integer',
            'next_retry_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PaymentInvoice::class, 'invoice_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
