<?php

namespace App\Filament\Guru\Resources\LearningAchievements\Pages;

use App\Filament\Guru\Resources\LearningAchievements\LearningAchievementResource;
use App\Models\Schedule;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\Access\AuthorizationException;

class CreateLearningAchievement extends CreateRecord
{
    protected static string $resource = LearningAchievementResource::class;

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
                    throw new AuthorizationException('Anda tidak memiliki akses untuk mata pelajaran ini.');
                }
            }
        }

        return $data;
    }
}
