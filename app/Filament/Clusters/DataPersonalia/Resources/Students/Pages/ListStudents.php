<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Students\Pages;

use App\Filament\Actions\DownloadImportTemplateAction;
use App\Filament\Actions\ImportPersonaliaAction;
use App\Filament\Clusters\DataPersonalia\Resources\Students\StudentResource;
use App\Filament\Imports\StudentImporter;
use App\Models\Student;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DownloadImportTemplateAction::make('student'),
            ImportPersonaliaAction::make('importStudents')
                ->personaliaType('student')
                ->importer(StudentImporter::class)
                ->maxRows(500)
                ->chunkSize(50)
                ->authorize(fn (): bool => auth()->user()?->can('create', Student::class) ?? false),
            CreateAction::make(),
        ];
    }
}
