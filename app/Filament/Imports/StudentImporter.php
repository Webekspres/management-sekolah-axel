<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\UsesPersonaliaImportQueue;
use App\Models\Student;
use App\Services\Personalia\StudentImportService;
use App\Support\Import\ImportColumnCatalog;
use App\Support\Import\ImportColumnFactory;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class StudentImporter extends Importer
{
    use UsesPersonaliaImportQueue;

    protected static ?string $model = Student::class;

    public static function getColumns(): array
    {
        return ImportColumnFactory::fromDefinitions(ImportColumnCatalog::studentColumns());
    }

    public function resolveRecord(): Student
    {
        return new Student;
    }

    public function fillRecord(): void
    {
        //
    }

    public function saveRecord(): void
    {
        app(StudentImportService::class)->createFromRow($this->data, $this->options);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $failedRowsCount = $import->getFailedRowsCount();

        if ($failedRowsCount) {
            return __('personalia.import.notifications.student_completed_with_failures', [
                'successful' => number_format($import->successful_rows),
                'failed' => number_format($failedRowsCount),
            ]);
        }

        return __('personalia.import.notifications.student_completed', [
            'successful' => number_format($import->successful_rows),
        ]);
    }
}
