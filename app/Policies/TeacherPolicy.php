<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;
use App\Support\TemporaryAccessManager;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', Teacher::class)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function view(User $user, Teacher $teacher): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $teacher)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Teacher::class)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function update(User $user, Teacher $teacher): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $teacher)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $teacher)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function restore(User $user, Teacher $teacher): bool
    {
        return false;
    }

    public function forceDelete(User $user, Teacher $teacher): bool
    {
        return false;
    }

    private function hasTemporaryAccess(User $user, string $ability, mixed $target): bool
    {
        return app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, $ability, $target);
    }
}
