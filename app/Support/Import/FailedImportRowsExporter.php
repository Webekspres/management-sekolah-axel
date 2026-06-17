<?php

namespace App\Support\Import;

use Filament\Actions\Imports\Models\FailedImportRow;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\File;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class FailedImportRowsExporter
{
    public function downloadFilename(Import $import): string
    {
        return __('personalia.import.failure_xlsx.file_name', [
            'import_id' => $import->getKey(),
            'csv_name' => (string) str($import->file_name)->beforeLast('.')->remove('.'),
        ]).'.xlsx';
    }

    public function buildToTempFile(Import $import): string
    {
        $tempPath = storage_path('app/temp/imports/'.uniqid('failed_', true).'.xlsx');

        File::ensureDirectoryExists(dirname($tempPath));

        $writer = new Writer;
        $writer->openToFile($tempPath);

        /** @var ?FailedImportRow $firstFailedRow */
        $firstFailedRow = $import->failedRows()->first();

        $columnHeaders = $firstFailedRow ? array_keys($firstFailedRow->data) : [];
        $columnHeaders[] = __('personalia.import.failure_xlsx.error_header');

        $headerStyle = (new Style)->setFontBold();
        $writer->addRow(Row::fromValuesWithStyles($columnHeaders, $headerStyle));

        $errorStyle = (new Style)->setFontColor(Color::rgb(185, 28, 28));

        $import->failedRows()
            ->lazyById(100)
            ->each(function (FailedImportRow $failedImportRow) use ($writer, $errorStyle): void {
                $values = array_map(
                    fn (mixed $value): string => $this->sanitizeCell($value),
                    array_values($failedImportRow->data),
                );

                $values[] = $failedImportRow->validation_error
                    ?? __('filament-actions::import.failure_csv.system_error');

                $errorColumnIndex = count($values) - 1;

                $writer->addRow(Row::fromValuesWithStyles(
                    $values,
                    null,
                    [$errorColumnIndex => $errorStyle],
                ));
            });

        $writer->close();

        return $tempPath;
    }

    private function sanitizeCell(mixed $value): string
    {
        $string = (string) ($value ?? '');

        if ($string !== '' && in_array($string[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$string;
        }

        return $string;
    }
}
