<?php

namespace App\Policies;

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

        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru', 'siswa_ortu'], true);
    }

    public function view(User $user, Announcement $announcement): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $announcement)) {
            return true;
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        return in_array($user->role, $announcement->target_role ?? [], true);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Announcement::class)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $announcement)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $announcement)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah'], true);
    }

    public function restore(User $user, Announcement $announcement): bool
    {
        if ($this->hasTemporaryAccess($user, 'restore', $announcement)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
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
