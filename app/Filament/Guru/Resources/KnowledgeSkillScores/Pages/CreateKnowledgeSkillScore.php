<?php

namespace App\Filament\Guru\Resources\KnowledgeSkillScores\Pages;

use App\Filament\Guru\Resources\KnowledgeSkillScores\KnowledgeSkillScoreResource;
use App\Models\KnowledgeSkillScore;
use App\Models\Schedule;
use App\Models\User;
use App\Services\RaporService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

class CreateKnowledgeSkillScore extends CreateRecord
{
    protected static string $resource = KnowledgeSkillScoreResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->role === 'guru' && $user->teacher) {
            $subjectId = $data['subject_id'] ?? null;

            if ($subjectId) {
                $ownsSubject = Schedule::where('teacher_id', $user->teacher->id)
                    ->where('subject_id', $subjectId)
                    ->exists();

                if (! $ownsSubject) {
                    throw new AuthorizationException('Anda tidak memiliki akses untuk menginput nilai mata pelajaran ini.');
                }
            }
        }

        $raporService = app(RaporService::class);

        if (isset($data['knowledge_score']) && $data['knowledge_score'] !== null) {
            $data['knowledge_predicate'] = $raporService->assignPredicate((float) $data['knowledge_score']);
        }

        if (isset($data['skill_score']) && $data['skill_score'] !== null) {
            $data['skill_predicate'] = $raporService->assignPredicate((float) $data['skill_score']);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Use updateOrCreate to handle the unique constraint (student_id, subject_id, academic_year_id)
        return KnowledgeSkillScore::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'subject_id' => $data['subject_id'],
                'academic_year_id' => $data['academic_year_id'],
            ],
            collect($data)->except(['student_id', 'subject_id', 'academic_year_id'])->all(),
        );
    }
}
