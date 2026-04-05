<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Referral;
use App\Models\User;

class ReferralPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Referral $referral): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Referral $referral): bool
    {
        return true;
    }

    public function delete(User $user, Referral $referral): bool
    {
        return $user->role === UserRole::Admin;
    }
}
