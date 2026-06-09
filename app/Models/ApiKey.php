<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'merchant_id', 'name', 'key_hash', 'key_prefix',
        'permissions', 'ip_whitelist', 'is_active', 'expires_at', 'last_used_at',
    ];

    protected $hidden = ['key_hash'];

    protected function casts(): array
    {
        return [
            'permissions'  => 'array',
            'ip_whitelist' => 'array',
            'is_active'    => 'boolean',
            'expires_at'   => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    // Generates a raw key, stores its hash, returns raw key (shown once)
    public static function generate(Merchant $merchant, string $name, array $permissions = []): array
    {
        $raw    = 'cryn_' . Str::random(48);
        $prefix = substr($raw, 0, 12);
        $hash   = hash('sha256', $raw);

        $model = self::create([
            'merchant_id' => $merchant->id,
            'name'        => $name,
            'key_hash'    => $hash,
            'key_prefix'  => $prefix,
            'permissions' => $permissions,
        ]);

        return ['model' => $model, 'raw_key' => $raw];
    }

    public static function findByRawKey(string $raw): ?self
    {
        $hash = hash('sha256', $raw);

        return self::where('key_hash', $hash)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();
    }

    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return true; // no restrictions = full access
        }

        return in_array($permission, $this->permissions, true);
    }
}
