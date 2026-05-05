<?php

namespace App\Filament\Guru\Resources\PersonalityScores\Pages;

use App\Filament\Guru\Resources\PersonalityScores\PersonalityScoreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPersonalityScores extends ListRecords
{
    protected static string $resource = PersonalityScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
