<?php

namespace App\Filament\Clusters\Academic\Resources\AcademicYears\Pages;

use App\Filament\Clusters\Academic\Resources\AcademicYears\AcademicYearResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademicYear extends CreateRecord
{
    protected static string $resource = AcademicYearResource::class;
}
