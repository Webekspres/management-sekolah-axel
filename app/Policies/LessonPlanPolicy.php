<?php

namespace App\Policies;

use App\Enums\UserRole;
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

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function view(User $user, LessonPlan $lessonPlan): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $lessonPlan)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::Guru) || ! $user->teacher) {
            return false;
        }

        return $lessonPlan->teacher_id === $user->teacher->id;
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', LessonPlan::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::Guru);
    }

    public function update(User $user, LessonPlan $lessonPlan): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $lessonPlan)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::Guru) || ! $user->teacher) {
            return false;
        }

        if ($lessonPlan->teacher_id !== $user->teacher->id) {
            return false;
        }

        return in_array($lessonPlan->status, ['DRAFT', 'REVISED'], true);
    }

    public function delete(User $user, LessonPlan $lessonPlan): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $lessonPlan)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
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
