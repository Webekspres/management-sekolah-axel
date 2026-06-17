<?php

namespace App\Filament\Kepsek\Resources\Attendances\Pages;

use App\Filament\Kepsek\Resources\Attendances\AttendanceResource;
use Filament\Resources\Pages\ListRecords;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;
}
