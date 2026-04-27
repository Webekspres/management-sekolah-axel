<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;
use App\Support\TemporaryAccessManager;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', Schedule::class)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function view(User $user, Schedule $schedule): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $schedule)) {
            return true;
        }

        if (in_array($user->role, ['super_admin', 'kepala_sekolah'], true)) {
            return true;
        }

        if ($user->role === 'guru' && $user->teacher) {
            return $schedule->teacher_id === $user->teacher->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Schedule::class)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function update(User $user, Schedule $schedule): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $schedule)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $schedule)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function restore(User $user, Schedule $schedule): bool
    {
        return false;
    }

    public function forceDelete(User $user, Schedule $schedule): bool
    {
        return false;
    }

    private function hasTemporaryAccess(User $user, string $ability, mixed $target): bool
    {
        return app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, $ability, $target);
    }
}
