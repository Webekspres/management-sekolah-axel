<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\Concerns\InteractsWithTemporaryAccess;

class StaffPolicy
{
    use InteractsWithTemporaryAccess;

    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', User::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah);
    }

    public function view(User $user, User $staff): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $staff)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', User::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function update(User $user, User $staff): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $staff)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function delete(User $user, User $staff): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $staff)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function restore(User $user, User $staff): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $staff): bool
    {
        return false;
    }
}
