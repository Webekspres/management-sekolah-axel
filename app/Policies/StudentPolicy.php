<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Support\TemporaryAccessManager;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', Student::class)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function view(User $user, Student $student): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $student)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Student::class)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function update(User $user, Student $student): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $student)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function delete(User $user, Student $student): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $student)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function restore(User $user, Student $student): bool
    {
        return false;
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return false;
    }

    private function hasTemporaryAccess(User $user, string $ability, mixed $target): bool
    {
        return app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, $ability, $target);
    }
}
