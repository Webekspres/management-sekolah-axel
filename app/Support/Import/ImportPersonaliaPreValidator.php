<?php

namespace App\Support\Import;

use App\Filament\Actions\ImportPersonaliaAction;
use Filament\Actions\Imports\Importer;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportPersonaliaPreValidator
{
    /**
     * @param  array<string, mixed>  $data
     * @param  class-string<Importer>  $importerClass
     */
    public static function validate(
        array $data,
        string $personaliaType,
        string $importerClass,
        ImportPersonaliaAction $action,
    ): void {
        $errors = [];

        $file = $data['file'] ?? null;

        if (! $file instanceof TemporaryUploadedFile) {
            $errors['file'] = __('personalia.import.errors.file_required');

            throw ValidationException::withMessages($errors);
        }

        if ($personaliaType === 'student' && blank(session('active_academic_level_id'))) {
            throw ValidationException::withMessages([
                'file' => __('personalia.import.errors.select_level_first'),
            ]);
        }

        /** @var array<string, string|null> $columnMap */
        $columnMap = $data['columnMap'] ?? [];

        $missingColumns = ImportPersonaliaFileInspector::unmappedRequiredColumns($importerClass, $columnMap);

        if ($missingColumns !== []) {
            $errors['columnMap'] = __('personalia.import.errors.unmapped_required_columns', [
                'columns' => implode(', ', $missingColumns),
            ]);
        }

        $rowCount = ImportPersonaliaFileInspector::countDataRows($action, $file);

        if ($rowCount === 0) {
            $errors['file'] = __('personalia.import.errors.no_data_rows');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
