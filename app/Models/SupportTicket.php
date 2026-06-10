<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id', 'subject', 'status', 'priority',
        'last_message_at', 'user_unread', 'admin_unread',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'user_unread'     => 'boolean',
            'admin_unread'    => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id');
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
