<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    protected $fillable = [
        'owner_id', 'member_id', 'email', 'role', 'sections', 'status',
    ];

    protected function casts(): array
    {
        return ['sections' => 'array'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
