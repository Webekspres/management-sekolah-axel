<?php

namespace App\Policies;

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

        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $attendance)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Attendance::class)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'guru'], true);
    }

    public function update(User $user, Attendance $attendance): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $attendance)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'guru'], true);
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $attendance)) {
            return true;
        }

        return $user->role === 'super_admin';
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
