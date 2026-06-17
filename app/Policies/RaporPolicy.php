<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Rapor;
use App\Models\SchoolClass;
use App\Models\User;
use App\Policies\Concerns\InteractsWithTemporaryAccess;

class RaporPolicy
{
    use InteractsWithTemporaryAccess;

    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', Rapor::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function view(User $user, Rapor $rapor): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $rapor)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::Guru) && $user->teacher) {
            return $this->isWaliKelasForRapor($user, $rapor);
        }

        if ($user->hasUserRole(UserRole::SiswaOrtu) && $user->student) {
            return $rapor->student_id === $user->student->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', Rapor::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::Guru);
    }

    public function update(User $user, Rapor $rapor): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $rapor)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin)) {
            return true;
        }

        if ($rapor->isFinalized() || $rapor->isApproved()) {
            return false;
        }

        if ($user->hasUserRole(UserRole::Guru) && $user->teacher) {
            return $this->isWaliKelasForRapor($user, $rapor);
        }

        return false;
    }

    public function delete(User $user, Rapor $rapor): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $rapor)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    /**
     * Wali Kelas (guru who is teacher_id of the student's class) can finalize rapor.
     */
    public function finalize(User $user, Rapor $rapor): bool
    {
        if ($user->hasUserRole(UserRole::SuperAdmin)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::Guru) || ! $user->teacher) {
            return false;
        }

        return $this->isWaliKelasForRapor($user, $rapor);
    }

    /**
     * Kepala Sekolah and super_admin can approve rapors.
     */
    public function approve(User $user, Rapor $rapor): bool
    {
        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah);
    }

    /**
     * Kepala Sekolah and super_admin can reject rapors.
     */
    public function reject(User $user, Rapor $rapor): bool
    {
        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah);
    }

    /**
     * Download authorization for rapor PDF route and UI actions.
     */
    public function download(User $user, Rapor $rapor): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $rapor)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::Guru) && $user->teacher) {
            return $this->isWaliKelasForRapor($user, $rapor);
        }

        if ($user->hasUserRole(UserRole::SiswaOrtu) && $user->student) {
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
