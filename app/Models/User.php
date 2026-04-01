<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Traits\Auditable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use Auditable, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'title',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    // --- Relationships ---

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'caseworker_id');
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class, 'provided_by');
    }

    // --- Panel Access ---

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    // --- Helpers ---

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSupervisor(): bool
    {
        return $this->role === UserRole::Supervisor;
    }

    public function isCaseworker(): bool
    {
        return $this->role === UserRole::Caseworker;
    }
}
