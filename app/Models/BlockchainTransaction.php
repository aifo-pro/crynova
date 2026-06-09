<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainTransaction extends Model
{
    protected $fillable = [
        'invoice_id', 'currency_id', 'tx_hash', 'from_address', 'to_address',
        'amount', 'fee', 'confirmations', 'confirmations_required',
        'direction', 'status', 'block_number', 'block_hash', 'block_time', 'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'amount'                  => 'decimal:18',
            'fee'                     => 'decimal:18',
            'confirmations'           => 'integer',
            'confirmations_required'  => 'integer',
            'block_number'            => 'integer',
            'block_time'              => 'datetime',
            'raw_data'                => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PaymentInvoice::class, 'invoice_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function isConfirmed(): bool
    {
        return $this->confirmations >= $this->confirmations_required;
    }
}
