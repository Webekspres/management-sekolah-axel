<?php

namespace App\Support\Import;

use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\SheetInterface;

class XlsxToCsvConverter
{
    public const DATA_SHEET_NAME = 'Isi Data';

    /**
     * @param  array<int, ImportColumnDefinition>  $columnDefinitions
     */
    public function convert(string $xlsxPath, array $columnDefinitions): string
    {
        $reader = ReaderFactory::createFromFile($xlsxPath);

        try {
            $reader->open($xlsxPath);
        } catch (\Throwable) {
            throw new RowImportFailedException(__('personalia.import.errors.invalid_xlsx'));
        }

        $sheet = $this->resolveDataSheet($reader);

        if (! $sheet) {
            $reader->close();

            throw new RowImportFailedException(__('personalia.import.errors.invalid_sheet'));
        }

        $outputPath = storage_path('app/temp/imports/'.uniqid('import_', true).'.csv');
        $directory = dirname($outputPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $handle = fopen($outputPath, 'w');

        if ($handle === false) {
            $reader->close();

            throw new RowImportFailedException(__('personalia.import.errors.invalid_xlsx'));
        }

        fwrite($handle, "\xEF\xBB\xBF");

        $exampleRow = array_map(
            fn (ImportColumnDefinition $column): ?string => $column->example(),
            $columnDefinitions,
        );

        $rowIndex = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $rowIndex++;
            $cells = array_map(
                fn ($cell): string => trim((string) ($cell?->getValue() ?? '')),
                $row->getCells(),
            );

            if ($rowIndex === 1) {
                fputcsv($handle, $cells);

                continue;
            }

            if ($this->shouldSkipTemplateRow($rowIndex, $cells, $columnDefinitions, $exampleRow)) {
                continue;
            }

            if ($this->isEmptyRow($cells)) {
                continue;
            }

            fputcsv($handle, $cells);
        }

        fclose($handle);
        $reader->close();

        return $outputPath;
    }

    /**
     * @param  iterable<ReaderInterface>  $reader
     */
    private function resolveDataSheet(mixed $reader): ?SheetInterface
    {
        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getName() === self::DATA_SHEET_NAME) {
                return $sheet;
            }
        }

        foreach ($reader->getSheetIterator() as $sheet) {
            return $sheet;
        }

        return null;
    }

    /**
     * @param  array<int, string>  $cells
     * @param  array<int, ImportColumnDefinition>  $columnDefinitions
     * @param  array<int, string|null>  $exampleRow
     */
    private function shouldSkipTemplateRow(int $rowIndex, array $cells, array $columnDefinitions, array $exampleRow): bool
    {
        if ($rowIndex === 2) {
            $hints = array_map(
                fn (ImportColumnDefinition $column): string => $column->formatHint(),
                $columnDefinitions,
            );

            return $this->rowMatches($cells, $hints);
        }

        if ($rowIndex === 3) {
            return $this->rowMatches($cells, array_map(
                fn (?string $value): string => $value ?? '',
                $exampleRow,
            ));
        }

        return false;
    }

    /**
     * @param  array<int, string>  $cells
     * @param  array<int, string>  $expected
     */
    private function rowMatches(array $cells, array $expected): bool
    {
        $limit = min(count($cells), count($expected));
        $matches = 0;
        $checked = 0;

        for ($i = 0; $i < $limit; $i++) {
            if ($expected[$i] === '') {
                continue;
            }

            $checked++;

            if (str($cells[$i])->lower()->toString() === str($expected[$i])->lower()->toString()) {
                $matches++;
            }
        }

        return $checked > 0 && $matches >= (int) ceil($checked * 0.6);
    }

    /**
     * @param  array<int, string>  $cells
     */
    private function isEmptyRow(array $cells): bool
    {
        return collect($cells)->every(fn (string $value): bool => $value === '');
    }
}
