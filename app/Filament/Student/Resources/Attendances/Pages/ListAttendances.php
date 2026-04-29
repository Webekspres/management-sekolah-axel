<?php

namespace App\Filament\Student\Resources\Attendances\Pages;

use App\Filament\Student\Resources\Attendances\AttendanceResource;
use Filament\Resources\Pages\ListRecords;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;
}
