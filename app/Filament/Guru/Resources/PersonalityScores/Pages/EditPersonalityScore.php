<?php

namespace App\Filament\Guru\Resources\PersonalityScores\Pages;

use App\Filament\Guru\Resources\PersonalityScores\PersonalityScoreResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPersonalityScore extends EditRecord
{
    protected static string $resource = PersonalityScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
