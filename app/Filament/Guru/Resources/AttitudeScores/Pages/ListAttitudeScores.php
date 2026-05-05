<?php

namespace App\Filament\Guru\Resources\AttitudeScores\Pages;

use App\Filament\Guru\Resources\AttitudeScores\AttitudeScoreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAttitudeScores extends ListRecords
{
    protected static string $resource = AttitudeScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
