<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FollowUp;
use App\Models\User;

class FollowUpPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FollowUp $followUp): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FollowUp $followUp): bool
    {
        return true;
    }

    public function delete(User $user, FollowUp $followUp): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true);
    }
}
