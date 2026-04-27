<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;
use App\Support\TemporaryAccessManager;

class SchoolClassPolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', SchoolClass::class)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah'], true);
    }

    public function view(User $user, SchoolClass $schoolClass): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $schoolClass)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah'], true);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', SchoolClass::class)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function update(User $user, SchoolClass $schoolClass): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $schoolClass)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function delete(User $user, SchoolClass $schoolClass): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $schoolClass)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function restore(User $user, SchoolClass $schoolClass): bool
    {
        return false;
    }

    public function forceDelete(User $user, SchoolClass $schoolClass): bool
    {
        return false;
    }

    private function hasTemporaryAccess(User $user, string $ability, mixed $target): bool
    {
        return app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, $ability, $target);
    }
}
