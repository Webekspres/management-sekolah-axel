<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SubjectKkm;
use App\Models\User;
use App\Policies\Concerns\InteractsWithTemporaryAccess;

class SubjectKkmPolicy
{
    use InteractsWithTemporaryAccess;

    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', SubjectKkm::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function view(User $user, SubjectKkm $subjectKkm): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $subjectKkm)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', SubjectKkm::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function update(User $user, SubjectKkm $subjectKkm): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $subjectKkm)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function delete(User $user, SubjectKkm $subjectKkm): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $subjectKkm)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function restore(User $user, SubjectKkm $subjectKkm): bool
    {
        return false;
    }

    public function forceDelete(User $user, SubjectKkm $subjectKkm): bool
    {
        return false;
    }
}
