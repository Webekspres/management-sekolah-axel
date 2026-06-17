<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PersonalityScore;
use App\Models\SchoolClass;
use App\Models\User;
use App\Policies\Concerns\InteractsWithTemporaryAccess;

class PersonalityScorePolicy
{
    use InteractsWithTemporaryAccess;

    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', PersonalityScore::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function view(User $user, PersonalityScore $personalityScore): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $personalityScore)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::Guru) || ! $user->teacher) {
            return false;
        }

        return $this->isWaliKelasForStudent($user, $personalityScore->student_id);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', PersonalityScore::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::Guru);
    }

    public function update(User $user, PersonalityScore $personalityScore): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $personalityScore)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::Guru) || ! $user->teacher) {
            return false;
        }

        return $this->isWaliKelasForStudent($user, $personalityScore->student_id);
    }

    public function delete(User $user, PersonalityScore $personalityScore): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $personalityScore)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function restore(User $user, PersonalityScore $personalityScore): bool
    {
        return false;
    }

    public function forceDelete(User $user, PersonalityScore $personalityScore): bool
    {
        return false;
    }

    /**
     * Check if the guru is the wali kelas (teacher_id) of the student's class.
     */
    private function isWaliKelasForStudent(User $user, string $studentId): bool
    {
        return SchoolClass::whereHas('students', fn ($q) => $q->where('id', $studentId))
            ->where('teacher_id', $user->teacher->id)
            ->exists();
    }
}
