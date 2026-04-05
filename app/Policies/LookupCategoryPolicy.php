<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LookupCategory;
use App\Models\User;

class LookupCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, LookupCategory $category): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, LookupCategory $category): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, LookupCategory $category): bool
    {
        return $user->role === UserRole::Admin && ! $category->is_system;
    }
}
