<?php

namespace App\Policies;

use App\Models\Rapor;
use App\Models\SchoolClass;
use App\Models\User;

class RaporPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function view(User $user, Rapor $rapor): bool
    {
        if (in_array($user->role, ['super_admin', 'kepala_sekolah'], true)) {
            return true;
        }

        if ($user->role === 'guru' && $user->teacher) {
            return $this->isWaliKelasForRapor($user, $rapor);
        }

        if ($user->role === 'siswa_ortu' && $user->student) {
            return $rapor->student_id === $user->student->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'guru'], true);
    }

    public function update(User $user, Rapor $rapor): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        if ($user->role === 'guru' && $user->teacher) {
            return $this->isWaliKelasForRapor($user, $rapor);
        }

        return false;
    }

    public function delete(User $user, Rapor $rapor): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * Wali Kelas (guru who is teacher_id of the student's class) can finalize rapor.
     */
    public function finalize(User $user, Rapor $rapor): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        if ($user->role !== 'guru' || ! $user->teacher) {
            return false;
        }

        return $this->isWaliKelasForRapor($user, $rapor);
    }

    /**
     * Kepala Sekolah and super_admin can approve rapors.
     */
    public function approve(User $user, Rapor $rapor): bool
    {
        return in_array($user->role, ['super_admin', 'kepala_sekolah'], true);
    }

    /**
     * Kepala Sekolah and super_admin can reject rapors.
     */
    public function reject(User $user, Rapor $rapor): bool
    {
        return in_array($user->role, ['super_admin', 'kepala_sekolah'], true);
    }

    /**
     * Siswa can download their own APPROVED rapor. super_admin can download any.
     */
    public function download(User $user, Rapor $rapor): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        if ($user->role === 'siswa_ortu' && $user->student) {
            return $rapor->student_id === $user->student->id
                && $rapor->isApproved();
        }

        return false;
    }

    public function restore(User $user, Rapor $rapor): bool
    {
        return false;
    }

    public function forceDelete(User $user, Rapor $rapor): bool
    {
        return false;
    }

    /**
     * Check if the guru is the wali kelas (teacher_id) of the student's class.
     */
    private function isWaliKelasForRapor(User $user, Rapor $rapor): bool
    {
        $rapor->loadMissing('student');

        if (! $rapor->student) {
            return false;
        }

        return SchoolClass::where('id', $rapor->student->class_id)
            ->where('teacher_id', $user->teacher->id)
            ->exists();
    }
}
