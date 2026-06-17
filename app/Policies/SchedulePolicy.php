<?php

namespace App\Policies;

use App\Enums\UserRole;
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

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function view(User $user, Schedule $schedule): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $schedule)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::Guru) && $user->teacher) {
            return $schedule->teacher_id === $user->teacher->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Schedule::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function update(User $user, Schedule $schedule): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $schedule)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $schedule)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
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
