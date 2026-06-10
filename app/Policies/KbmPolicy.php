<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Kbm;
use App\Models\User;
use App\Support\TemporaryAccessManager;

class KbmPolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', Kbm::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function view(User $user, Kbm $kbm): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $kbm)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::Guru) || ! $user->teacher) {
            return false;
        }

        return $kbm->schedule?->teacher_id === $user->teacher->id;
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Kbm::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::Guru);
    }

    public function update(User $user, Kbm $kbm): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $kbm)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::Guru) || ! $user->teacher) {
            return false;
        }

        if ($kbm->schedule?->teacher_id !== $user->teacher->id) {
            return false;
        }

        return in_array($kbm->status, ['DRAFT', 'PENDING'], true);
    }

    public function delete(User $user, Kbm $kbm): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $kbm)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function restore(User $user, Kbm $kbm): bool
    {
        return false;
    }

    public function forceDelete(User $user, Kbm $kbm): bool
    {
        return false;
    }

    private function hasTemporaryAccess(User $user, string $ability, mixed $target): bool
    {
        return app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, $ability, $target);
    }
}
