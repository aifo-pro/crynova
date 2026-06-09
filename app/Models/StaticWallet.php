<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StaticWallet extends Model
{
    protected $fillable = [
        'uuid', 'merchant_id', 'currency_id', 'address',
        'memo', 'client_identifier', 'hd_path', 'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $w) {
            if (empty($w->uuid)) {
                $w->uuid = (string) Str::uuid();
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
}
