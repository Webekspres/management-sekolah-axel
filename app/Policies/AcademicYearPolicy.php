<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\User;
use App\Support\TemporaryAccessManager;

class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', AcademicYear::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function view(User $user, AcademicYear $academicYear): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $academicYear)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', AcademicYear::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $academicYear)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function delete(User $user, AcademicYear $academicYear): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $academicYear)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function restore(User $user, AcademicYear $academicYear): bool
    {
        return false;
    }

    public function forceDelete(User $user, AcademicYear $academicYear): bool
    {
        return false;
    }

    private function hasTemporaryAccess(User $user, string $ability, mixed $target): bool
    {
        return app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, $ability, $target);
    }
}
