<?php

namespace App\Policies;

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

        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function view(User $user, SubjectKkm $subjectKkm): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $subjectKkm)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', SubjectKkm::class)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function update(User $user, SubjectKkm $subjectKkm): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $subjectKkm)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function delete(User $user, SubjectKkm $subjectKkm): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $subjectKkm)) {
            return true;
        }

        return $user->role === 'super_admin';
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
