<?php

namespace App\Policies;

use App\Models\SubjectKkm;
use App\Models\User;

class SubjectKkmPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function view(User $user, SubjectKkm $subjectKkm): bool
    {
        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function create(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    public function update(User $user, SubjectKkm $subjectKkm): bool
    {
        return $user->role === 'super_admin';
    }

    public function delete(User $user, SubjectKkm $subjectKkm): bool
    {
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
