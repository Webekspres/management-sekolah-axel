<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\User;
use App\Support\TemporaryAccessManager;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', Announcement::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru, UserRole::SiswaOrtu);
    }

    public function view(User $user, Announcement $announcement): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $announcement)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin)) {
            return true;
        }

        return in_array($user->role, $announcement->target_role ?? [], true);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Announcement::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $announcement)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $announcement)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah);
    }

    public function restore(User $user, Announcement $announcement): bool
    {
        if ($this->hasTemporaryAccess($user, 'restore', $announcement)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function forceDelete(User $user, Announcement $announcement): bool
    {
        return false;
    }

    private function hasTemporaryAccess(User $user, string $ability, mixed $target): bool
    {
        return app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, $ability, $target);
    }
}
