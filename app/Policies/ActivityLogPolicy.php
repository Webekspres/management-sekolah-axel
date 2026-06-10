<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use App\Policies\Concerns\InteractsWithTemporaryAccess;

class ActivityLogPolicy
{
    use InteractsWithTemporaryAccess;

    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', ActivityLog::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $activityLog)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function delete(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function restore(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function forceDelete(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }
}
