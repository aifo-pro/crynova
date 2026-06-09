<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'email_verified_at', 'telegram', 'language', 'notification_prefs', 'password',
        'role', 'referral_code', 'referred_by', 'google2fa_secret', 'google2fa_enabled',
        'account_api_key_encrypted', 'is_active', 'block_reason', 'blocked_at', 'last_login_at', 'last_login_ip',
    ];

    protected $hidden = [
        'password', 'remember_token', 'google2fa_secret', 'account_api_key_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'last_login_at'      => 'datetime',
            'password'           => 'hashed',
            'google2fa_enabled'  => 'boolean',
            'is_active'          => 'boolean',
            'blocked_at'         => 'datetime',
            'notification_prefs' => 'array',
        ];
    }

    // Account API key (encrypted, retrievable for masked display)
    public function setAccountApiKeyAttribute(?string $value): void
    {
        $this->attributes['account_api_key_encrypted'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccountApiKeyAttribute(): ?string
    {
        if (empty($this->attributes['account_api_key_encrypted'])) {
            return null;
        }
        try {
            return Crypt::decryptString($this->attributes['account_api_key_encrypted']);
        } catch (\Exception) {
            return null;
        }
    }

    // 2FA secret encrypted at rest — never exposed in JSON
    public function setGoogle2faSecretAttribute(?string $value): void
    {
        $this->attributes['google2fa_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getGoogle2faSecretAttribute(?string $value): ?string
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

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (empty($user->referral_code)) {
                $user->referral_code = strtoupper(\Illuminate\Support\Str::random(8));
            }
        });
    }

    /** A user can own multiple merchants (stores). */
    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }

    /** Users this user has referred. */
    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /** Team members this user has granted access to. */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'owner_id');
    }

    /** Saved payout addresses (address book). */
    public function savedAddresses(): HasMany
    {
        return $this->hasMany(SavedAddress::class);
    }

    /**
     * Backward-compatible accessor: the user's primary (most recent) merchant.
     * Prefer iterating merchants() for the multi-merchant UI.
     */
    public function merchant(): HasOne
    {
        return $this->hasOne(Merchant::class)->latestOfMany();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isMerchant(): bool
    {
        return $this->role === 'merchant';
    }

    public function isSupport(): bool
    {
        return $this->role === 'support';
    }
}
