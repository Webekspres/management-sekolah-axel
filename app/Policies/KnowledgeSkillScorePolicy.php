<?php

namespace App\Policies;

use App\Models\KnowledgeSkillScore;
use App\Models\Schedule;
use App\Models\User;

class KnowledgeSkillScorePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true);
    }

    public function view(User $user, KnowledgeSkillScore $knowledgeSkillScore): bool
    {
        if (in_array($user->role, ['super_admin', 'kepala_sekolah'], true)) {
            return true;
        }

        if ($user->role !== 'guru' || ! $user->teacher) {
            return false;
        }

        return $this->guruOwnsSubject($user, $knowledgeSkillScore->subject_id);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'guru'], true);
    }

    public function update(User $user, KnowledgeSkillScore $knowledgeSkillScore): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        if ($user->role !== 'guru' || ! $user->teacher) {
            return false;
        }

        return $this->guruOwnsSubject($user, $knowledgeSkillScore->subject_id);
    }

    public function delete(User $user, KnowledgeSkillScore $knowledgeSkillScore): bool
    {
        return $user->role === 'super_admin';
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
