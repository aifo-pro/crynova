<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTemplate extends Model
{
    protected $fillable = [
        'title', 'category', 'body', 'body_en', 'body_pl', 'body_ru',
        'is_active', 'sort', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Localized body for a given locale, falling back to English then Ukrainian.
     */
    public function bodyFor(?string $locale): string
    {
        $map = [
            'en' => $this->body_en,
            'pl' => $this->body_pl,
            'ru' => $this->body_ru,
            'uk' => $this->body,
        ];

        return trim((string) ($map[$locale] ?? '')) !== ''
            ? (string) $map[$locale]
            : (string) (trim((string) $this->body_en) !== '' ? $this->body_en : $this->body);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
