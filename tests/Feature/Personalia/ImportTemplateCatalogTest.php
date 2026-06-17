<?php

use App\Support\Import\ImportColumnCatalog;
use App\Support\Import\XlsxToCsvConverter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

test('converter reads isi data sheet and skips template hint and example rows', function () {
    $path = sys_get_temp_dir().'/template-test-'.uniqid('', true).'.xlsx';

    $writer = new Writer;
    $writer->openToFile($path);

    $sheet = $writer->getCurrentSheet();
    $sheet->setName(XlsxToCsvConverter::DATA_SHEET_NAME);

    $definitions = ImportColumnCatalog::studentColumns();

    $writer->addRow(Row::fromValues(array_map(
        fn ($column) => $column->label(),
        $definitions,
    )));

    $writer->addRow(Row::fromValues(array_map(
        fn ($column) => $column->formatHint(),
        $definitions,
    )));

    $writer->addRow(Row::fromValues(array_map(
        fn ($column) => $column->example() ?? '',
        $definitions,
    )));

    $dataRow = ImportColumnCatalog::studentExampleRow();
    $dataRow[0] = 'Siswa Import Test';
    $dataRow[1] = 'import.test@example.test';
    $writer->addRow(Row::fromValues($dataRow));

    $writer->close();

    $csvPath = app(XlsxToCsvConverter::class)->convert($path, $definitions);
    $lines = file($csvPath, FILE_IGNORE_NEW_LINES);

    expect($lines)->toHaveCount(2)
        ->and($lines[1])->toContain('Siswa Import Test')
        ->and($lines[1])->toContain('import.test@example.test');

    @unlink($path);
    @unlink($csvPath);
});

test('catalog exposes student and teacher column labels', function () {
    expect(ImportColumnCatalog::studentHeaderRow()[0])->toBe(__('personalia.import.columns.nama'))
        ->and(ImportColumnCatalog::teacherHeaderRow()[0])->toBe(__('personalia.import.columns.nama'));
});
