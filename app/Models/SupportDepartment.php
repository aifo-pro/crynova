<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportDepartment extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'support_department_user');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'department_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
