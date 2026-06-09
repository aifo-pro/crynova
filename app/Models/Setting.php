<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'is_encrypted', 'type', 'description'];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        // Cache the resolved scalar value (never the Eloquent model itself —
        // caching the model can break on deserialization across cache drivers).
        $cached = Cache::remember("setting:{$key}", 300, function () use ($key) {
            $setting = static::where('key', $key)->first();

            if (! $setting) {
                return ['found' => false];
            }

            $value = $setting->is_encrypted
                ? Crypt::decryptString($setting->value)
                : $setting->value;

            $resolved = match ($setting->type) {
                'int'  => (int) $value,
                'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($value, true),
                default => $value,
            };

            return ['found' => true, 'value' => $resolved];
        });

        return ($cached['found'] ?? false) ? $cached['value'] : $default;
    }

    public static function set(
        string $key,
        mixed $value,
        bool $encrypt = false,
        string $type = 'string',
        string $group = 'general',
        ?string $description = null,
    ): void
    {
        $raw = is_array($value) ? json_encode($value) : (string) $value;

        static::updateOrCreate(
            ['key' => $key],
            [
                'group'        => $group,
                'value'        => $encrypt ? Crypt::encryptString($raw) : $raw,
                'is_encrypted' => $encrypt,
                'type'         => $type,
                'description'  => $description,
            ],
        );

        Cache::forget("setting:{$key}");
    }
}
