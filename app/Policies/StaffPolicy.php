<?php

namespace App\Policies;

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

        return in_array($user->role, ['super_admin', 'kepala_sekolah'], true);
    }

    public function view(User $user, User $staff): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $staff)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah'], true);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', User::class)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function update(User $user, User $staff): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $staff)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function delete(User $user, User $staff): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $staff)) {
            return true;
        }

        return $user->role === 'super_admin';
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
