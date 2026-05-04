<?php

namespace App\Policies;

use App\Models\LearningAchievement;
use App\Models\Schedule;
use App\Models\User;

class LearningAchievementPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function view(User $user, LearningAchievement $learningAchievement): bool
    {
        if (in_array($user->role, ['super_admin', 'kepala_sekolah'], true)) {
            return true;
        }

        if ($user->role !== 'guru' || ! $user->teacher) {
            return false;
        }

        return $this->guruOwnsSubject($user, $learningAchievement->subject_id);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'guru'], true);
    }

    public function update(User $user, LearningAchievement $learningAchievement): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        if ($user->role !== 'guru' || ! $user->teacher) {
            return false;
        }

        return $this->guruOwnsSubject($user, $learningAchievement->subject_id);
    }

    public function delete(User $user, LearningAchievement $learningAchievement): bool
    {
        return $user->role === 'super_admin';
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
