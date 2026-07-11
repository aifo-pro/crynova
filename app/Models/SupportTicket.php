<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id', 'assigned_to', 'department_id', 'subject', 'status', 'priority', 'locale',
        'last_message_at', 'user_unread', 'admin_unread',
    ];

    /** Languages an agent can pick for template replies. */
    public const LOCALES = ['uk' => 'Українська', 'en' => 'English', 'pl' => 'Polski', 'ru' => 'Русский'];

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

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(SupportDepartment::class, 'department_id');
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(SupportInternalNote::class, 'ticket_id');
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /** Language used for template replies: agent's choice, else the user's, else Ukrainian. */
    public function effectiveLocale(): string
    {
        $locale = $this->locale ?: ($this->user?->language ?: 'uk');

        return array_key_exists($locale, self::LOCALES) ? $locale : 'uk';
    }

    /** Priority display metadata. */
    public function priorityMeta(): array
    {
        return match ($this->priority) {
            'high', 'urgent' => ['label' => 'Високий', 'class' => 'bg-rose-50 text-rose-600 ring-rose-200'],
            'low'            => ['label' => 'Низький', 'class' => 'bg-slate-100 text-slate-500 ring-slate-200'],
            default          => ['label' => 'Звичайний', 'class' => 'bg-blue-50 text-blue-600 ring-blue-200'],
        };
    }
}
