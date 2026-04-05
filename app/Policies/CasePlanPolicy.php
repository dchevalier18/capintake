<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CasePlan;
use App\Models\User;

class CasePlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CasePlan $casePlan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CasePlan $casePlan): bool
    {
        return true;
    }

    public function delete(User $user, CasePlan $casePlan): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true);
    }
}
