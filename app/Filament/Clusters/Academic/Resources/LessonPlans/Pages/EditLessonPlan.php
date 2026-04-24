<?php

namespace App\Filament\Clusters\Academic\Resources\LessonPlans\Pages;

use App\Filament\Clusters\Academic\Resources\LessonPlans\LessonPlanResource;
use Filament\Resources\Pages\EditRecord;

class EditLessonPlan extends EditRecord
{
    protected static string $resource = LessonPlanResource::class;
}
