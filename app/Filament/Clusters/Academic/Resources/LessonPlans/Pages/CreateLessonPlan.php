<?php

namespace App\Filament\Clusters\Academic\Resources\LessonPlans\Pages;

use App\Filament\Clusters\Academic\Resources\LessonPlans\LessonPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLessonPlan extends CreateRecord
{
    protected static string $resource = LessonPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = $data['status'] ?? 'DRAFT';
        $data['revision_note'] = $data['revision_note'] ?? null;

        return $data;
    }
}
