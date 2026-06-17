<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\HasPersonaliaImportNotifications;
use App\Filament\Imports\Concerns\UsesPersonaliaImportQueue;
use App\Models\Teacher;
use App\Services\Personalia\TeacherImportService;
use App\Support\Import\ImportColumnCatalog;
use App\Support\Import\ImportColumnFactory;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class TeacherImporter extends Importer
{
    use HasPersonaliaImportNotifications;
    use UsesPersonaliaImportQueue;

    protected static ?string $model = Teacher::class;

    public static function getColumns(): array
    {
        return ImportColumnFactory::fromDefinitions(ImportColumnCatalog::teacherColumns());
    }

    public function resolveRecord(): Teacher
    {
        return new Teacher;
    }

    public function fillRecord(): void
    {
        //
    }

    public function saveRecord(): void
    {
        app(TeacherImportService::class)->createFromRow($this->data, $this->options);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $failedRowsCount = $import->getFailedRowsCount();

        if ($failedRowsCount) {
            return __('personalia.import.notifications.teacher_completed_with_failures', [
                'successful' => number_format($import->successful_rows),
                'failed' => number_format($failedRowsCount),
            ]);
        }

        return __('personalia.import.notifications.teacher_completed', [
            'successful' => number_format($import->successful_rows),
        ]);
    }
}
