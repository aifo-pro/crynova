<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class IntegrationModule extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'long_description', 'icon', 'image_path', 'version',
        'file_path', 'external_url', 'is_active', 'sort',
        'name_en', 'name_pl', 'name_ru', 'description_en', 'description_pl', 'description_ru',
        'long_description_en', 'long_description_pl', 'long_description_ru',
    ];

    /** Localized value of a field by current locale, falling back to the base (uk) value. */
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
            'is_active' => 'boolean',
            'sort'      => 'integer',
        ];
    }

    /** A module can be downloaded if active and has either a file or an external URL. */
    public function isDownloadable(): bool
    {
        return $this->is_active && ($this->file_path || $this->external_url);
    }

    /** Public URL for an uploaded file (null if module uses an external link). */
    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    /** Public URL for the module photo (null if none uploaded). */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
