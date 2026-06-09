<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterMailing extends Model
{
    protected $fillable = ['sent_by', 'subject', 'body', 'recipients_count', 'sent_at'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
