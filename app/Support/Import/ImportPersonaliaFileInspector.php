<?php

namespace App\Support\Import;

use App\Filament\Actions\ImportPersonaliaAction;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use League\Csv\Reader as CsvReader;
use League\Csv\Statement;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportPersonaliaFileInspector
{
    public static function countDataRows(ImportPersonaliaAction $action, TemporaryUploadedFile $file): int
    {
        $stream = $action->getUploadedFileStream($file);

        if (! $stream) {
            return 0;
        }

        try {
            $csvReader = CsvReader::from($stream);

            if (filled($csvDelimiter = $action->getCsvDelimiter($csvReader))) {
                $csvReader->setDelimiter($csvDelimiter);
            }

            $csvReader->setHeaderOffset($action->getHeaderOffset() ?? 0);

            return (new Statement)->process($csvReader)->count();
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * @param  class-string<Importer>  $importerClass
     * @param  array<string, string|null>  $columnMap
     * @return list<string>
     */
    public static function unmappedRequiredColumns(string $importerClass, array $columnMap): array
    {
        $missing = [];

        foreach ($importerClass::getColumns() as $column) {
            if (! $column instanceof ImportColumn) {
                continue;
            }

            if (! $column->isMappingRequired()) {
                continue;
            }

            if (blank($columnMap[$column->getName()] ?? null)) {
                $missing[] = $column->getLabel();
            }
        }

        return $missing;
    }
}
