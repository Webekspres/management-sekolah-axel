<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\User;
use App\Policies\Concerns\InteractsWithTemporaryAccess;

class GradePolicy
{
    use InteractsWithTemporaryAccess;

    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', Grade::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function view(User $user, Grade $grade): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $grade)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::Guru) || ! $user->teacher) {
            return false;
        }

        return $this->guruOwnsGradeSubject($user, $grade);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Grade::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::Guru);
    }

    public function update(User $user, Grade $grade): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $grade)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::Guru) || ! $user->teacher) {
            return false;
        }

        return $this->guruOwnsGradeSubject($user, $grade);
    }

    public function delete(User $user, Grade $grade): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $grade)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
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
