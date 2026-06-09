<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = [
        'code', 'name', 'network', 'contract_address', 'decimals',
        'confirmations_required', 'min_amount', 'max_amount',
        'estimated_fee', 'is_active', 'supports_memo', 'node_config',
    ];

    protected function casts(): array
    {
        return [
            'decimals'               => 'integer',
            'confirmations_required' => 'integer',
            'min_amount'             => 'decimal:18',
            'max_amount'             => 'decimal:18',
            'estimated_fee'          => 'decimal:18',
            'is_active'              => 'boolean',
            'supports_memo'          => 'boolean',
            'node_config'            => 'array',
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PaymentInvoice::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BlockchainTransaction::class);
    }

    public function isToken(): bool
    {
        return ! empty($this->contract_address);
    }
}
