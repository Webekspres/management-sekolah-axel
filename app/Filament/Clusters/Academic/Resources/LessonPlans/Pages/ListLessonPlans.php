<?php

namespace App\Filament\Clusters\Academic\Resources\LessonPlans\Pages;

use App\Filament\Clusters\Academic\Resources\LessonPlans\LessonPlanResource;
use Filament\Resources\Pages\ListRecords;

class ListLessonPlans extends ListRecords
{
    protected static string $resource = LessonPlanResource::class;
}
