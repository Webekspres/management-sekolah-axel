<?php

namespace App\Filament\Clusters\Academic\Resources\SubjectKkms\Pages;

use App\Filament\Clusters\Academic\Resources\SubjectKkms\SubjectKkmResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubjectKkms extends ListRecords
{
    protected static string $resource = SubjectKkmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
