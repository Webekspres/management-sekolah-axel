<?php

namespace App\Filament\Guru\Resources\PersonalityScores\Pages;

use App\Filament\Guru\Resources\PersonalityScores\PersonalityScoreResource;
use App\Models\PersonalityScore;
use App\Models\SchoolClass;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

class CreatePersonalityScore extends CreateRecord
{
    protected static string $resource = PersonalityScoreResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->role === 'guru' && $user->teacher) {
            $studentId = $data['student_id'] ?? null;

            if ($studentId) {
                $isWaliKelas = SchoolClass::where('teacher_id', $user->teacher->id)
                    ->whereHas('students', fn ($q) => $q->where('id', $studentId))
                    ->exists();

                if (! $isWaliKelas) {
                    throw new AuthorizationException('Anda tidak memiliki akses untuk menginput kepribadian siswa ini.');
                }
            }
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Use updateOrCreate to handle the unique constraint (student_id, academic_year_id)
        return PersonalityScore::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'academic_year_id' => $data['academic_year_id'],
            ],
            collect($data)->except(['student_id', 'academic_year_id'])->all(),
        );
    }
}
