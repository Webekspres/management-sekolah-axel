<?php

namespace App\Filament\Clusters\Academic\Resources\SubjectKkms\Pages;

use App\Filament\Clusters\Academic\Resources\SubjectKkms\SubjectKkmResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubjectKkm extends EditRecord
{
    protected static string $resource = SubjectKkmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
