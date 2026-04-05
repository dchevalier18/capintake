<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CsbgExpenditure;
use App\Models\User;

class CsbgExpenditurePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor]);
    }

    public function view(User $user, CsbgExpenditure $expenditure): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor]);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, CsbgExpenditure $expenditure): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, CsbgExpenditure $expenditure): bool
    {
        return $user->role === UserRole::Admin;
    }
}
