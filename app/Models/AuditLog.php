<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null; // append-only: no updates

    protected $fillable = [
        'user_id', 'actor_type', 'actor_ip', 'action',
        'subject_id', 'subject_type', 'old_values', 'new_values', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(
        string $action,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        string $actorType = 'user',
    ): void {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        static::create([
            'user_id'      => $user?->id,
            'actor_type'   => $actorType,
            'actor_ip'     => request()->ip(),
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'old_values'   => $oldValues ?: null,
            'new_values'   => $newValues ?: null,
            'user_agent'   => request()->userAgent(),
        ]);
    }
}
