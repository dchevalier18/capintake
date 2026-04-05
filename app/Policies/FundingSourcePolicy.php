<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FundingSource;
use App\Models\User;

class FundingSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true);
    }

    public function view(User $user, FundingSource $fundingSource): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true);
    }

    public function update(User $user, FundingSource $fundingSource): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true);
    }

    public function delete(User $user, FundingSource $fundingSource): bool
    {
        return $user->role === UserRole::Admin;
    }
}
