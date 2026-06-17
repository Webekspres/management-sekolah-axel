<?php

namespace App\Filament\Guru\Resources\LessonPlans\Pages;

use App\Filament\Guru\Resources\LessonPlans\LessonPlanResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateLessonPlan extends CreateRecord
{
    protected static string $resource = LessonPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()->teacher) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Akun guru belum memiliki profil guru.',
            ]);
        }

        $data['teacher_id'] = auth()->user()->teacher->id;
        $data['status'] = 'DRAFT';
        $data['revision_note'] = null;

        return $data;
    }
}
