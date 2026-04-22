<?php

namespace App\Filament\Clusters\Academic\Resources\AcademicYears\Pages;

use App\Filament\Clusters\Academic\Resources\AcademicYears\AcademicYearResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAcademicYear extends EditRecord
{
    protected static string $resource = AcademicYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
