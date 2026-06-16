<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = [
        'title', 'slug', 'body', 'meta_title', 'meta_description', 'is_published',
        'title_en', 'title_pl', 'body_en', 'body_pl',
        'meta_title_en', 'meta_title_pl', 'meta_description_en', 'meta_description_pl',
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
