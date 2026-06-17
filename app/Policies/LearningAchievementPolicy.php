<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LearningAchievement;
use App\Models\Schedule;
use App\Models\User;
use App\Policies\Concerns\InteractsWithTemporaryAccess;

class LearningAchievementPolicy
{
    use InteractsWithTemporaryAccess;

    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', LearningAchievement::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function view(User $user, LearningAchievement $learningAchievement): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $learningAchievement)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::Guru) || ! $user->teacher) {
            return false;
        }

        return $this->guruOwnsSubject($user, $learningAchievement->subject_id);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', LearningAchievement::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::Guru);
    }

    public function update(User $user, LearningAchievement $learningAchievement): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $learningAchievement)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::Guru) || ! $user->teacher) {
            return false;
        }

        return $this->guruOwnsSubject($user, $learningAchievement->subject_id);
    }

    public function delete(User $user, LearningAchievement $learningAchievement): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $learningAchievement)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function restore(User $user, LearningAchievement $learningAchievement): bool
    {
        return false;
    }

    public function forceDelete(User $user, LearningAchievement $learningAchievement): bool
    {
        return false;
    }

    /**
     * Check if the guru has a schedule for the given subject.
     */
    private function guruOwnsSubject(User $user, string $subjectId): bool
    {
        return Schedule::where('subject_id', $subjectId)
            ->where('teacher_id', $user->teacher->id)
            ->exists();
    }
}
