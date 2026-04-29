<?php

namespace App\Filament\Clusters\Academic\Resources\Attendances\Pages;

use App\Filament\Clusters\Academic\Resources\Attendances\AttendanceResource;
use App\Filament\Clusters\Academic\Resources\Attendances\Schemas\AttendanceForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;

    public function form(Schema $schema): Schema
    {
        return AttendanceForm::configureForEdit($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
