<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportMessage extends Model
{
    protected $fillable = [
        'ticket_id', 'user_id', 'is_admin', 'is_system', 'body', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_admin'  => 'boolean',
            'is_system' => 'boolean',
            'meta'      => 'array',
        ];
    }

    /**
     * Localized display body. System messages carry a translation key + params
     * in `meta` so each viewer sees them in their own language; falls back to the
     * stored body for regular or legacy messages.
     */
    public function displayBody(): string
    {
        if ($this->is_system && ! empty($this->meta['key'])) {
            return __($this->meta['key'], $this->meta['params'] ?? []);
        }

        return (string) $this->body;
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportAttachment::class, 'message_id');
    }
}
