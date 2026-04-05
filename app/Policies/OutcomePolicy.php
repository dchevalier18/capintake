<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Outcome;
use App\Models\User;

class OutcomePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Outcome $outcome): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Outcome $outcome): bool
    {
        return true;
    }

    public function delete(User $user, Outcome $outcome): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true);
    }

    public function restore(User $user, Outcome $outcome): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true);
    }

    public function forceDelete(User $user, Outcome $outcome): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Only supervisors and admins can verify outcomes.
     */
    public function verify(User $user, Outcome $outcome): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true);
    }
}
