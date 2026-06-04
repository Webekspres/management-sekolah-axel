<?php

namespace App\Policies;

use App\Models\AttitudeScore;
use App\Models\SchoolClass;
use App\Models\User;
use App\Policies\Concerns\InteractsWithTemporaryAccess;

class AttitudeScorePolicy
{
    use InteractsWithTemporaryAccess;

    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', AttitudeScore::class)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function view(User $user, AttitudeScore $attitudeScore): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $attitudeScore)) {
            return true;
        }

        if (in_array($user->role, ['super_admin', 'kepala_sekolah'], true)) {
            return true;
        }

        if ($user->role !== 'guru' || ! $user->teacher) {
            return false;
        }

        return $this->isWaliKelasForStudent($user, $attitudeScore->student_id);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', AttitudeScore::class)) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'guru'], true);
    }

    public function update(User $user, AttitudeScore $attitudeScore): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $attitudeScore)) {
            return true;
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        if ($user->role !== 'guru' || ! $user->teacher) {
            return false;
        }

        return $this->isWaliKelasForStudent($user, $attitudeScore->student_id);
    }

    public function delete(User $user, AttitudeScore $attitudeScore): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $attitudeScore)) {
            return true;
        }

        return $user->role === 'super_admin';
    }

    public function restore(User $user, AttitudeScore $attitudeScore): bool
    {
        return false;
    }

    public function forceDelete(User $user, AttitudeScore $attitudeScore): bool
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
