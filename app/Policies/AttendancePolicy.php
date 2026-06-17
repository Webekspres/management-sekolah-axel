<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\User;
use App\Policies\Concerns\InteractsWithTemporaryAccess;

class AttendancePolicy
{
    use InteractsWithTemporaryAccess;

    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', Attendance::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $attendance)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Attendance::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::Guru);
    }

    public function update(User $user, Attendance $attendance): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $attendance)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::Guru);
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $attendance)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function restore(User $user, Attendance $attendance): bool
    {
        return false;
    }

    public function forceDelete(User $user, Attendance $attendance): bool
    {
        return false;
    }
}
