<?php

namespace App\Policies;

use App\Models\LessonPlan;
use App\Models\User;
use App\Support\TemporaryAccessManager;

class LessonPlanPolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', LessonPlan::class)) {
            return true;
        }

        $role = $user->effectiveRole();

        return in_array($role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function view(User $user, LessonPlan $lessonPlan): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $lessonPlan)) {
            return true;
        }

        $role = $user->effectiveRole();

        if (in_array($role, ['super_admin', 'kepala_sekolah'], true)) {
            return true;
        }

        if ($role !== 'guru' || ! $user->teacher) {
            return false;
        }

        return $lessonPlan->teacher_id === $user->teacher->id;
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', LessonPlan::class)) {
            return true;
        }

        $role = $user->effectiveRole();

        return in_array($role, ['super_admin', 'guru'], true);
    }

    public function update(User $user, LessonPlan $lessonPlan): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $lessonPlan)) {
            return true;
        }

        $role = $user->effectiveRole();

        if (in_array($role, ['super_admin', 'kepala_sekolah'], true)) {
            return true;
        }

        if ($role !== 'guru' || ! $user->teacher) {
            return false;
        }

        if ($lessonPlan->teacher_id !== $user->teacher->id) {
            return false;
        }

        return in_array($lessonPlan->status, ['DRAFT', 'PENDING'], true);
    }

    public function delete(User $user, LessonPlan $lessonPlan): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $lessonPlan)) {
            return true;
        }

        $role = $user->effectiveRole();

        if ($role === 'super_admin') {
            return true;
        }

        if ($role !== 'guru' || ! $user->teacher) {
            return false;
        }

        if ($lessonPlan->teacher_id !== $user->teacher->id) {
            return false;
        }

        return $lessonPlan->status !== 'APPROVED';
    }

    public function restore(User $user, LessonPlan $lessonPlan): bool
    {
        return false;
    }

    public function forceDelete(User $user, LessonPlan $lessonPlan): bool
    {
        return false;
    }

    private function hasTemporaryAccess(User $user, string $ability, mixed $target): bool
    {
        return app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, $ability, $target);
    }
}
