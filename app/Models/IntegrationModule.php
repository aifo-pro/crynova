<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class IntegrationModule extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'version',
        'file_path', 'external_url', 'is_active', 'sort',
    ];

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
}
