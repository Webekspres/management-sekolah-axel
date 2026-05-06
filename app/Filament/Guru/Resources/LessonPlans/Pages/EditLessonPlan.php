<?php

namespace App\Filament\Guru\Resources\LessonPlans\Pages;

use App\Filament\Guru\Resources\LessonPlans\LessonPlanResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditLessonPlan extends EditRecord
{
    protected static string $resource = LessonPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! in_array($this->record->status, ['DRAFT', 'REVISED'], true)) {
            throw ValidationException::withMessages([
                'status' => 'RPP hanya dapat diubah saat berstatus Draft atau Revisi.',
            ]);
        }

        return $data;
    }
}
