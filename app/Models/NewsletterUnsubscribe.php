<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterUnsubscribe extends Model
{
    protected $fillable = ['email', 'token', 'unsubscribed_at', 'source'];

    protected function casts(): array
    {
        return [
            'unsubscribed_at' => 'datetime',
        ];
    }

    public static function tokenFor(string $email): string
    {
        $normalized = mb_strtolower(trim($email));

        return static::firstOrCreate(
            ['email' => $normalized],
            ['token' => Str::random(64)],
        )->token;
    }

    public static function isUnsubscribed(string $email): bool
    {
        return static::where('email', mb_strtolower(trim($email)))
            ->whereNotNull('unsubscribed_at')
            ->exists();
    }
}
