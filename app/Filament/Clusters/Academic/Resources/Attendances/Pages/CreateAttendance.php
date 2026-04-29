<?php

namespace App\Filament\Clusters\Academic\Resources\Attendances\Pages;

use App\Filament\Clusters\Academic\Resources\Attendances\AttendanceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
