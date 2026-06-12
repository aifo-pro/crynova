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
    ];

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
