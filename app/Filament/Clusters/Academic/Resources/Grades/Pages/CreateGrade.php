<?php

namespace App\Filament\Clusters\Academic\Resources\Grades\Pages;

use App\Filament\Clusters\Academic\Resources\Grades\GradeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGrade extends CreateRecord
{
    protected static string $resource = GradeResource::class;
}
