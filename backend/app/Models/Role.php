<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_name',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the users that have this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withTimestamps();
    }

    /**
     * Scope to search roles by name.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('role_name', 'like', "%{$search}%");
    }

    /**
     * Get the count of users with this role.
     */
    public function getUserCountAttribute(): int
    {
        return $this->users()->count();
    }
}
