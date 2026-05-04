<?php

namespace App\Policies;

use App\Models\PersonalityScore;
use App\Models\SchoolClass;
use App\Models\User;

class PersonalityScorePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function view(User $user, PersonalityScore $personalityScore): bool
    {
        if (in_array($user->role, ['super_admin', 'kepala_sekolah'], true)) {
            return true;
        }

        if ($user->role !== 'guru' || ! $user->teacher) {
            return false;
        }

        return $this->isWaliKelasForStudent($user, $personalityScore->student_id);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'guru'], true);
    }

    public function update(User $user, PersonalityScore $personalityScore): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        if ($user->role !== 'guru' || ! $user->teacher) {
            return false;
        }

        return $this->isWaliKelasForStudent($user, $personalityScore->student_id);
    }

    public function delete(User $user, PersonalityScore $personalityScore): bool
    {
        return $user->role === 'super_admin';
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
