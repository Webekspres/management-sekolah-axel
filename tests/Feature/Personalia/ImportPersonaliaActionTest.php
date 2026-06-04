<?php

use App\Filament\Actions\ImportPersonaliaAction;
use App\Filament\Imports\StudentImporter;

test('import personalia action allows xlsx file extension', function () {
    $action = ImportPersonaliaAction::make('importStudents')
        ->importer(StudentImporter::class);

    expect($action->getFileValidationRules()[0])->toBe('extensions:csv,txt,xlsx');
});
