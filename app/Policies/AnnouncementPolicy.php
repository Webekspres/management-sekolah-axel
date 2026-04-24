<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;
use App\Support\TemporaryAccessManager;

class AnnouncementPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', Announcement::class)) {
            return true;
        }

        $role = $user->effectiveRole();

        return in_array($role, ['super_admin', 'kepala_sekolah', 'guru', 'siswa_ortu'], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Announcement $announcement): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $announcement)) {
            return true;
        }

        $role = $user->effectiveRole();

        if ($role === 'super_admin') {
            return true;
        }

        return in_array($role, $announcement->target_role ?? [], true);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Announcement::class)) {
            return true;
        }

        $role = $user->effectiveRole();

        return in_array($role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Announcement $announcement): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $announcement)) {
            return true;
        }

        $role = $user->effectiveRole();

        return in_array($role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Announcement $announcement): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $announcement)) {
            return true;
        }

        $role = $user->effectiveRole();

        return in_array($role, ['super_admin', 'kepala_sekolah'], true);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Announcement $announcement): bool
    {
        if ($this->hasTemporaryAccess($user, 'restore', $announcement)) {
            return true;
        }

        $role = $user->effectiveRole();

        return in_array($role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Announcement $announcement): bool
    {
        return false;
    }

    private function hasTemporaryAccess(User $user, string $ability, mixed $target): bool
    {
        return app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, $ability, $target);
    }
}
