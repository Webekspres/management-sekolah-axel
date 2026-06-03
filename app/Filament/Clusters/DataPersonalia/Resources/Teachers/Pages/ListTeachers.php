<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Teachers\Pages;

use App\Filament\Actions\DownloadImportTemplateAction;
use App\Filament\Actions\ImportPersonaliaAction;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\TeacherResource;
use App\Filament\Imports\TeacherImporter;
use App\Models\Teacher;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeachers extends ListRecords
{
    protected static string $resource = TeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DownloadImportTemplateAction::make('teacher'),
            ImportPersonaliaAction::make('importTeachers')
                ->personaliaType('teacher')
                ->importer(TeacherImporter::class)
                ->maxRows(500)
                ->chunkSize(50)
                ->authorize(fn (): bool => auth()->user()?->can('create', Teacher::class) ?? false),
            CreateAction::make(),
        ];
    }
}
