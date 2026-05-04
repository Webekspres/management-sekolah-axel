<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\Schedule;
use App\Models\User;

class GradePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function view(User $user, Grade $grade): bool
    {
        if (in_array($user->role, ['super_admin', 'kepala_sekolah'], true)) {
            return true;
        }

        if ($user->role !== 'guru' || ! $user->teacher) {
            return false;
        }

        return $this->guruOwnsGradeSubject($user, $grade);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'guru'], true);
    }

    public function update(User $user, Grade $grade): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        if ($user->role !== 'guru' || ! $user->teacher) {
            return false;
        }

        return $this->guruOwnsGradeSubject($user, $grade);
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $user->role === 'super_admin';
    }

    public function restore(User $user, Grade $grade): bool
    {
        return false;
    }

    public function forceDelete(User $user, Grade $grade): bool
    {
        return false;
    }

    /**
     * Check if the guru's teacher record has a schedule for the grade's subject.
     */
    private function guruOwnsGradeSubject(User $user, Grade $grade): bool
    {
        return Schedule::where('subject_id', $grade->subject_id)
            ->where('teacher_id', $user->teacher->id)
            ->exists();
    }
}
