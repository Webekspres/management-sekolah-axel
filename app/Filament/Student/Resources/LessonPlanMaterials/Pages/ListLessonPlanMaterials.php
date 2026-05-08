<?php

namespace App\Filament\Student\Resources\LessonPlanMaterials\Pages;

use App\Filament\Student\Resources\LessonPlanMaterials\LessonPlanMaterialResource;
use Filament\Resources\Pages\ListRecords;

class ListLessonPlanMaterials extends ListRecords
{
    protected static string $resource = LessonPlanMaterialResource::class;
}
