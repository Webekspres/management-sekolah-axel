<?php

use App\Filament\Actions\ImportPersonaliaAction;
use App\Filament\Imports\StudentImporter;
use App\Models\User;
use App\Support\Import\ImportColumnCatalog;
use App\Support\Import\XlsxToCsvConverter;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Mockery\MockInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

test('import personalia action is authorized for super admin only', function () {
    $admin = User::factory()->asAdmin()->create();
    $guru = User::factory()->asGuru()->create();
    $siswa = User::factory()->asSiswa()->create();

    $action = ImportPersonaliaAction::make('importStudents')
        ->importer(StudentImporter::class);

    $this->actingAs($admin);
    expect($action->isAuthorized())->toBeTrue();

    $this->actingAs($guru);
    expect(ImportPersonaliaAction::make('importStudents')->importer(StudentImporter::class)->isAuthorized())->toBeFalse();

    $this->actingAs($siswa);
    expect(ImportPersonaliaAction::make('importStudents')->importer(StudentImporter::class)->isAuthorized())->toBeFalse();
});

test('import personalia action allows xlsx file extension', function () {
    $action = ImportPersonaliaAction::make('importStudents')
        ->importer(StudentImporter::class);

    expect($action->getFileValidationRules()[0])->toBe('extensions:csv,txt,xlsx');
});

test('import personalia file upload accepts xlsx mime types and extension map', function () {
    $action = ImportPersonaliaAction::make('importStudents')
        ->personaliaType('student')
        ->importer(StudentImporter::class);

    $schema = invade($action)->schema;
    $components = value($schema, $action);

    $fileUpload = collect($components)->first(
        fn ($component): bool => $component instanceof FileUpload && $component->getName() === 'file',
    );

    expect($fileUpload)->toBeInstanceOf(FileUpload::class)
        ->and($fileUpload->getAcceptedFileTypes())->toContain(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
        )
        ->and($fileUpload->getMimeTypeMap())->toHaveKey('xlsx');
});

test('import personalia action converts uploaded xlsx to readable csv stream', function () {
    $xlsxPath = sys_get_temp_dir().'/import-upload-test-'.uniqid('', true).'.xlsx';

    $writer = new Writer;
    $writer->openToFile($xlsxPath);

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
    $dataRow[0] = 'Siswa Upload Test';
    $dataRow[1] = 'upload.test@example.test';
    $writer->addRow(Row::fromValues($dataRow));

    $writer->close();

    /** @var TemporaryUploadedFile&MockInterface $file */
    $file = Mockery::mock(TemporaryUploadedFile::class);
    $file->shouldReceive('getClientOriginalExtension')->andReturn('xlsx');
    $file->shouldReceive('getRealPath')->andReturn($xlsxPath);
    $file->shouldReceive('getPathname')->andReturn($xlsxPath);

    $action = ImportPersonaliaAction::make('importStudents')
        ->personaliaType('student')
        ->importer(StudentImporter::class);

    $stream = $action->getUploadedFileStream($file);

    expect($stream)->not->toBeFalse();

    $csvContent = stream_get_contents($stream);

    expect($csvContent)->toContain('Siswa Upload Test')
        ->and($csvContent)->toContain('upload.test@example.test');

    @unlink($xlsxPath);
});
