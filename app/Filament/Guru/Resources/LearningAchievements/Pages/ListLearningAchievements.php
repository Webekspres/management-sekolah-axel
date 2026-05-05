<?php

namespace App\Filament\Guru\Resources\LearningAchievements\Pages;

use App\Filament\Guru\Resources\LearningAchievements\LearningAchievementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLearningAchievements extends ListRecords
{
    protected static string $resource = LearningAchievementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
