<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SelfSufficiencyAssessment;
use App\Models\User;

class SelfSufficiencyAssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SelfSufficiencyAssessment $assessment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SelfSufficiencyAssessment $assessment): bool
    {
        return true;
    }

    public function delete(User $user, SelfSufficiencyAssessment $assessment): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Supervisor], true);
    }
}
