<?php

use App\Filament\Actions\ImportPersonaliaAction;
use App\Filament\Imports\StudentImporter;
use App\Support\Import\ImportColumnCatalog;
use App\Support\Import\ImportPersonaliaFileInspector;
use App\Support\Import\ImportPersonaliaPreValidator;
use App\Support\Import\XlsxToCsvConverter;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Mockery\MockInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

test('pre validator rejects student import without academic level', function () {
    $action = ImportPersonaliaAction::make('importStudents')
        ->personaliaType('student')
        ->importer(StudentImporter::class);

    /** @var TemporaryUploadedFile&MockInterface $file */
    $file = Mockery::mock(TemporaryUploadedFile::class);

    expect(fn () => ImportPersonaliaPreValidator::validate(
        data: ['file' => $file, 'columnMap' => []],
        personaliaType: 'student',
        importerClass: StudentImporter::class,
        action: $action,
    ))->toThrow(ValidationException::class);
})->withSession(['active_academic_level_id' => null]);

test('pre validator rejects files without data rows', function () {
    $xlsxPath = sys_get_temp_dir().'/empty-import-'.uniqid('', true).'.xlsx';

    $writer = new Writer;
    $writer->openToFile($xlsxPath);

    $sheet = $writer->getCurrentSheet();
    $sheet->setName(XlsxToCsvConverter::DATA_SHEET_NAME);

    $definitions = ImportColumnCatalog::studentColumns();

    $writer->addRow(Row::fromValues(array_map(
        fn ($column) => $column->label(),
        $definitions,
    )));

    $writer->close();

    $action = ImportPersonaliaAction::make('importStudents')
        ->personaliaType('student')
        ->importer(StudentImporter::class);

    /** @var TemporaryUploadedFile&MockInterface $file */
    $file = Mockery::mock(TemporaryUploadedFile::class);
    $file->shouldReceive('getClientOriginalExtension')->andReturn('xlsx');
    $file->shouldReceive('getRealPath')->andReturn($xlsxPath);
    $file->shouldReceive('getPathname')->andReturn($xlsxPath);

    $columnMap = collect(StudentImporter::getColumns())
        ->mapWithKeys(fn ($column) => [$column->getName() => $column->getLabel()])
        ->all();

    expect(fn () => ImportPersonaliaPreValidator::validate(
        data: ['file' => $file, 'columnMap' => $columnMap],
        personaliaType: 'student',
        importerClass: StudentImporter::class,
        action: $action,
    ))->toThrow(ValidationException::class);

    @unlink($xlsxPath);
})->withSession(['active_academic_level_id' => '01test-level-id']);

test('file inspector lists unmapped required columns', function () {
    $missing = ImportPersonaliaFileInspector::unmappedRequiredColumns(StudentImporter::class, [
        'nama' => 'Nama Lengkap',
    ]);

    expect($missing)->not->toBeEmpty()
        ->and($missing)->not->toContain('Nama Lengkap');
});
