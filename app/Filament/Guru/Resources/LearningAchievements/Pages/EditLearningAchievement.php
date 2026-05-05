<?php

namespace App\Filament\Guru\Resources\LearningAchievements\Pages;

use App\Filament\Guru\Resources\LearningAchievements\LearningAchievementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLearningAchievement extends EditRecord
{
    protected static string $resource = LearningAchievementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
