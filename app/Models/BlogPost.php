<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'author_id', 'title', 'slug', 'excerpt', 'body',
        'cover_image', 'tags', 'status', 'published_at',
        'rating_sum', 'rating_count',
        'title_en', 'title_pl', 'excerpt_en', 'excerpt_pl', 'body_en', 'body_pl',
    ];

    /** Localized value of a field by current locale, falling back to the default (uk) value. */
    public function tr(string $field): string
    {
        $loc = app()->getLocale();
        if ($loc !== 'uk') {
            $val = (string) ($this->{$field . '_' . $loc} ?? '');
            if ($val !== '') {
                return $val;
            }
        }
        return (string) ($this->{$field} ?? '');
    }

    /** Average star rating (0–5, one decimal). */
    public function ratingAverage(): float
    {
        return $this->rating_count > 0
            ? round($this->rating_sum / $this->rating_count, 1)
            : 0.0;
    }

    /** Estimated reading time in minutes (≈200 words/min). */
    public function readingMinutes(): int
    {
        $words = str_word_count(strip_tags((string) $this->body));

        return max(1, (int) ceil($words / 200));
    }

    protected function casts(): array
    {
        return [
            'tags'         => 'array',
            'published_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }
}
