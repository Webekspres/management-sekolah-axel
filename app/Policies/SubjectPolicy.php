<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;
use App\Support\TemporaryAccessManager;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', Subject::class)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah'], true);
    }

    public function view(User $user, Subject $subject): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $subject)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah'], true);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Subject::class)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function update(User $user, Subject $subject): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $subject)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function delete(User $user, Subject $subject): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $subject)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function restore(User $user, Subject $subject): bool
    {
        return false;
    }

    public function forceDelete(User $user, Subject $subject): bool
    {
        return false;
    }

    private function hasTemporaryAccess(User $user, string $ability, mixed $target): bool
    {
        return app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, $ability, $target);
    }
}
