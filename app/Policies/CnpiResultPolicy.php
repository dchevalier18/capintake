<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CnpiResult;
use App\Models\User;

class CnpiResultPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CnpiResult $cnpiResult): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true);
    }

    public function update(User $user, CnpiResult $cnpiResult): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true);
    }

    public function delete(User $user, CnpiResult $cnpiResult): bool
    {
        return $user->role === UserRole::Admin;
    }
}
