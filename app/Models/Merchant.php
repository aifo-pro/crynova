<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Merchant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'slug', 'shop_id', 'website', 'description', 'logo_path',
        'status', 'merchant_type', 'accept_type', 'domain', 'telegram_channel',
        'business_type', 'project_description', 'base_currency_code',
        'cms', 'success_url', 'fail_url', 'callback_url',
        'verification_code', 'verification_method', 'verified_at',
        'reject_reason', 'admin_note', 'moderated_by', 'moderated_at',
        'fee_percent', 'max_invoice_amount', 'daily_turnover_limit', 'monthly_turnover_limit',
        'transfer_fee_payer', 'service_fee_payer',
        'partial_confirm_value', 'partial_confirm_unit', 'aml_enabled',
        'postback_format', 'checkout_config', 'widget_config',
        'autoconvert_enabled', 'autoconvert_target_currency_id',
        'payout_addresses', 'webhook_url', 'webhook_secret',
        'api_key_encrypted', 'test_mode',
        'kyc_status', 'kyc_data', 'is_active',
    ];

    /** Payment acceptance type. */
    public const ACCEPT_WEBSITE  = 'website';   // accept on own site (needs domain verification)
    public const ACCEPT_DONATION = 'donation';  // hosted donation page (no domain verification)

    protected $hidden = ['webhook_secret', 'verification_code', 'api_key_encrypted'];

    /** Lifecycle status constants. */
    public const STATUS_UNVERIFIED = 'unverified';   // created, must verify ownership
    public const STATUS_MODERATION = 'moderation';   // verified, awaiting admin review
    public const STATUS_ACTIVE     = 'active';        // approved, full access
    public const STATUS_REJECTED   = 'rejected';      // rejected by admin (can resubmit)
    public const STATUS_BLOCKED    = 'blocked';       // suspended by admin

    protected function casts(): array
    {
        return [
            'payout_addresses' => 'array',
            'checkout_config'  => 'array',
            'widget_config'    => 'array',
            'kyc_data'         => 'array',
            'fee_percent'              => 'decimal:2',
            'max_invoice_amount'       => 'decimal:2',
            'daily_turnover_limit'     => 'decimal:2',
            'monthly_turnover_limit'   => 'decimal:2',
            'is_active'        => 'boolean',
            'test_mode'        => 'boolean',
            'aml_enabled'      => 'boolean',
            'autoconvert_enabled' => 'boolean',
            'verified_at'      => 'datetime',
            'moderated_at'     => 'datetime',
        ];
    }

    public function staticWallets(): HasMany
    {
        return $this->hasMany(StaticWallet::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $merchant) {
            if (empty($merchant->shop_id)) {
                $merchant->shop_id = \Illuminate\Support\Str::random(16);
            }
        });
    }

    // ── API key (display) ──────────────────────────────────────────
    // Stored encrypted so it can be shown masked + copied on the project card.
    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key_encrypted'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getApiKeyAttribute(): ?string
    {
        if (empty($this->attributes['api_key_encrypted'])) {
            return null;
        }
        try {
            return Crypt::decryptString($this->attributes['api_key_encrypted']);
        } catch (\Exception) {
            return null;
        }
    }

    /** Masked API key for display, e.g. "cryn_ab12…f9x0". */
    public function maskedApiKey(): ?string
    {
        $key = $this->api_key;
        if (! $key) {
            return null;
        }

        return strlen($key) > 18
            ? substr($key, 0, 10) . '…' . substr($key, -6)
            : $key;
    }

    /** Public hosted payment (POS) page URL. */
    public function paymentPageUrl(): string
    {
        return route('checkout.pos', $this->shop_id);
    }

    // Webhook secret encrypted at rest — required for outbound HMAC signatures
    public function setWebhookSecretAttribute(?string $value): void
    {
        $this->attributes['webhook_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getWebhookSecretAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    // ── Lifecycle helpers ──────────────────────────────────────────
    public function isActive(): bool      { return $this->status === self::STATUS_ACTIVE; }
    public function isUnverified(): bool  { return $this->status === self::STATUS_UNVERIFIED; }
    public function isOnModeration(): bool{ return $this->status === self::STATUS_MODERATION; }
    public function isRejected(): bool    { return $this->status === self::STATUS_REJECTED; }
    public function isBlocked(): bool     { return $this->status === self::STATUS_BLOCKED; }
    public function isVerified(): bool    { return $this->verified_at !== null; }

    /** Whether feature pages (invoices, payouts, widget…) are unlocked. */
    public function featuresUnlocked(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** Human-readable status label + colour token used by the UI. */
    public function statusMeta(): array
    {
        return match ($this->status) {
            self::STATUS_ACTIVE     => ['label' => __('merchant.status.active'),       'color' => 'emerald'],
            self::STATUS_MODERATION => ['label' => __('merchant.status.moderation'),   'color' => 'amber'],
            self::STATUS_UNVERIFIED => ['label' => __('merchant.status.unverified'),   'color' => 'blue'],
            self::STATUS_REJECTED   => ['label' => __('merchant.status.rejected'),     'color' => 'rose'],
            self::STATUS_BLOCKED    => ['label' => __('merchant.status.blocked'),      'color' => 'rose'],
            default                 => ['label' => __('merchant.status.unknown'),      'color' => 'slate'],
        };
    }

    /** Generate a fresh ownership-verification code (idempotent if already set). */
    public function ensureVerificationCode(): string
    {
        if (empty($this->verification_code)) {
            $this->verification_code = 'crynova-verify-' . \Illuminate\Support\Str::random(24);
            $this->save();
        }

        return $this->verification_code;
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PaymentInvoice::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(Balance::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    public function currencies(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Currency::class)
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    public function paymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function balanceFor(Currency $currency): Balance
    {
        return $this->balances()->firstOrCreate(
            ['currency_id' => $currency->id],
            ['available' => 0, 'locked' => 0],
        );
    }
}
