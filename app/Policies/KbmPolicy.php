<?php

namespace App\Policies;

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

        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function view(User $user, Kbm $kbm): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $kbm)) {
            return true;
        }

        if (in_array($user->role, ['super_admin', 'kepala_sekolah'], true)) {
            return true;
        }

        if ($user->role !== 'guru' || ! $user->teacher) {
            return false;
        }

        return $kbm->schedule?->teacher_id === $user->teacher->id;
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Kbm::class)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'guru'], true);
    }

    public function update(User $user, Kbm $kbm): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $kbm)) {
            return true;
        }

        if (in_array($user->role, ['super_admin', 'kepala_sekolah'], true)) {
            return true;
        }

        if ($user->role !== 'guru' || ! $user->teacher) {
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

        return $user->role === 'super_admin';
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
