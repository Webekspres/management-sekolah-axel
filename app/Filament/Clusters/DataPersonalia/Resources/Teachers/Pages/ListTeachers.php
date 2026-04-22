<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Teachers\Pages;

use App\Filament\Clusters\DataPersonalia\Resources\Teachers\TeacherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeachers extends ListRecords
{
    protected static string $resource = TeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
