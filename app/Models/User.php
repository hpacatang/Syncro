<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'profile_name',
        'email',
        'password',
        'role',
        'department_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(User::class, 'department_id', 'department_id')
            ->where('role', 'org');
    }


    public function displayName(): string
    {
        return $this->profile_name ?: $this->name;
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'pair' => 'PAIR',
            'department' => 'Department',
            'org' => 'Organization',
            default => ucfirst((string) $this->role),
        };
    }

    public static function departments()
    {
        return static::query()
            ->where('role', 'department')
            ->orderBy('profile_name')
            ->orderBy('name');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'super_admin';
    }

    public function isPair(): bool
    {
        return $this->role === 'pair';
    }

    public function isDepartment(): bool
    {
        return $this->role === 'department';
    }

    public function isOrg(): bool
    {
        return $this->role === 'org';
    }

    public function canSubmitPosts(): bool
    {
        return $this->isOrg() || $this->isDepartment();
    }

    public function isStaffReviewer(): bool
    {
        return $this->isAdmin() || $this->isPair();
    }

    public function homeRoute(): string
    {
        if ($this->canSubmitPosts()) {
            return 'org.dashboard';
        }

        if ($this->isSuperAdmin()) {
            return 'users.index';
        }

        if ($this->isStaffReviewer()) {
            return 'dashboard';
        }

        return 'dashboard';
    }
}
