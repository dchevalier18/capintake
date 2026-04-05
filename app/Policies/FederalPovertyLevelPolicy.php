<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FederalPovertyLevel;
use App\Models\User;

class FederalPovertyLevelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, FederalPovertyLevel $fpl): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, FederalPovertyLevel $fpl): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, FederalPovertyLevel $fpl): bool
    {
        return $user->role === UserRole::Admin;
    }
}
