<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = [
        'title', 'slug', 'body', 'meta_title', 'meta_description', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }
}
