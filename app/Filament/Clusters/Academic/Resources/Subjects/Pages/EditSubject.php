<?php

namespace App\Filament\Clusters\Academic\Resources\Subjects\Pages;

use App\Filament\Clusters\Academic\Resources\Subjects\SubjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubject extends EditRecord
{
    protected static string $resource = SubjectResource::class;

    public function getTitle(): string
    {
        return 'Ubah Mata Pelajaran';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
