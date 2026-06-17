<?php

namespace App\Filament\Guru\Resources\AttitudeScores\Pages;

use App\Filament\Guru\Resources\AttitudeScores\AttitudeScoreResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAttitudeScore extends EditRecord
{
    protected static string $resource = AttitudeScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
