<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class MerchantWebhook extends Model
{
    protected $fillable = [
        'merchant_id', 'url', 'secret_encrypted', 'events', 'is_active', 'last_triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'events'             => 'array',
            'is_active'          => 'boolean',
            'last_triggered_at'  => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    // Store signing secret encrypted; expose decrypted for HMAC signing
    public function getSecretAttribute(): ?string
    {
        return $this->secret_encrypted ? Crypt::decryptString($this->secret_encrypted) : null;
    }

    public static function createForMerchant(Merchant $merchant, string $url, array $events = []): array
    {
        $rawSecret = Str::random(32);

        $webhook = static::create([
            'merchant_id'      => $merchant->id,
            'url'              => $url,
            'secret_encrypted' => Crypt::encryptString($rawSecret),
            'events'           => $events ?: null,
        ]);

        return ['model' => $webhook, 'raw_secret' => $rawSecret];
    }
}
