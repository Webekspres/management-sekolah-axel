<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\KnowledgeSkillScore;
use App\Models\Schedule;
use App\Models\User;
use App\Policies\Concerns\InteractsWithTemporaryAccess;

class KnowledgeSkillScorePolicy
{
    use InteractsWithTemporaryAccess;

    public function viewAny(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'viewAny', KnowledgeSkillScore::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah, UserRole::Guru);
    }

    public function view(User $user, KnowledgeSkillScore $knowledgeSkillScore): bool
    {
        if ($this->hasTemporaryAccess($user, 'view', $knowledgeSkillScore)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin, UserRole::KepalaSekolah)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::Guru) || ! $user->teacher) {
            return false;
        }

        return $this->guruOwnsSubject($user, $knowledgeSkillScore->subject_id);
    }

    public function create(User $user): bool
    {
        if ($this->hasTemporaryAccess($user, 'create', KnowledgeSkillScore::class)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin, UserRole::Guru);
    }

    public function update(User $user, KnowledgeSkillScore $knowledgeSkillScore): bool
    {
        if ($this->hasTemporaryAccess($user, 'update', $knowledgeSkillScore)) {
            return true;
        }

        if ($user->hasUserRole(UserRole::SuperAdmin)) {
            return true;
        }

        if (! $user->hasUserRole(UserRole::Guru) || ! $user->teacher) {
            return false;
        }

        return $this->guruOwnsSubject($user, $knowledgeSkillScore->subject_id);
    }

    public function delete(User $user, KnowledgeSkillScore $knowledgeSkillScore): bool
    {
        if ($this->hasTemporaryAccess($user, 'delete', $knowledgeSkillScore)) {
            return true;
        }

        return $user->hasUserRole(UserRole::SuperAdmin);
    }

    public function restore(User $user, KnowledgeSkillScore $knowledgeSkillScore): bool
    {
        return false;
    }

    public function forceDelete(User $user, KnowledgeSkillScore $knowledgeSkillScore): bool
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
