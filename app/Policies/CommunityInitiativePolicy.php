<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CommunityInitiative;
use App\Models\User;

class CommunityInitiativePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor]);
    }

    public function view(User $user, CommunityInitiative $initiative): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor]);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor]);
    }

    public function update(User $user, CommunityInitiative $initiative): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor]);
    }

    public function delete(User $user, CommunityInitiative $initiative): bool
    {
        return $user->role === UserRole::Admin;
    }
}
