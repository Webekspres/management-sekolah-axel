<?php

namespace App\Filament\Guru\Resources\LessonPlans\Pages;

use App\Filament\Guru\Resources\LessonPlans\LessonPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLessonPlans extends ListRecords
{
    protected static string $resource = LessonPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
