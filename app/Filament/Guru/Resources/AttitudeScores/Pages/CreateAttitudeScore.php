<?php

namespace App\Filament\Guru\Resources\AttitudeScores\Pages;

use App\Filament\Guru\Resources\AttitudeScores\AttitudeScoreResource;
use App\Models\AttitudeScore;
use App\Models\SchoolClass;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

class CreateAttitudeScore extends CreateRecord
{
    protected static string $resource = AttitudeScoreResource::class;

    protected function beforeCreate(): void
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->role === 'guru' && $user->teacher) {
            $studentId = $this->data['student_id'] ?? null;

            if ($studentId) {
                $isWaliKelas = SchoolClass::where('teacher_id', $user->teacher->id)
                    ->whereHas('students', fn ($q) => $q->where('id', $studentId))
                    ->exists();

                if (! $isWaliKelas) {
                    throw new AuthorizationException('Anda tidak memiliki akses untuk menginput nilai sikap siswa ini.');
                }
            }
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Use updateOrCreate to handle the unique constraint (student_id, academic_year_id, aspect)
        return AttitudeScore::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'academic_year_id' => $data['academic_year_id'],
                'aspect' => $data['aspect'],
            ],
            [
                'score' => $data['score'],
                'description' => $data['description'] ?? null,
            ],
        );
    }
}
