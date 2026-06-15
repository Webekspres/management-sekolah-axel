<?php

use App\Filament\Clusters\DataPersonalia\Resources\Teachers\Pages\ListTeachers;
use App\Filament\Imports\StudentImporter;
use App\Models\User;
use App\Support\Import\FailedImportRowsExporter;
use Filament\Actions\Imports\Models\FailedImportRow;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use OpenSpout\Reader\Common\Creator\ReaderFactory;

test('failed import rows exporter builds xlsx with error column', function () {
    $import = Import::query()->create([
        'user_id' => User::factory()->asAdmin()->create()->id,
        'file_name' => 'template-import-siswa.xlsx',
        'file_path' => '/tmp/unused.csv',
        'importer' => StudentImporter::class,
        'total_rows' => 1,
        'processed_rows' => 1,
        'successful_rows' => 0,
        'completed_at' => now(),
    ]);

    FailedImportRow::query()->create([
        'import_id' => $import->id,
        'data' => [
            'Nama Lengkap' => 'testest',
            'Email' => 'tes@gmail.com',
            'Tanggal Ijazah' => '',
        ],
        'validation_error' => 'Tanggal Ijazah bukan tanggal yang valid.',
    ]);

    $exporter = app(FailedImportRowsExporter::class);
    $path = $exporter->buildToTempFile($import);

    expect($path)->toBeFile()
        ->and($exporter->downloadFilename($import))->toBe('impor-'.$import->id.'-template-import-siswa-gagal.xlsx');

    $reader = ReaderFactory::createFromFile($path);
    $reader->open($path);

    $rows = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = array_map(
                fn ($cell): string => trim((string) ($cell?->getValue() ?? '')),
                $row->getCells(),
            );
        }

        break;
    }

    $reader->close();
    @unlink($path);

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toContain(__('personalia.import.failure_xlsx.error_header'))
        ->and($rows[1][0])->toBe('testest')
        ->and($rows[1][1])->toBe('tes@gmail.com')
        ->and(end($rows[1]))->toBe('Tanggal Ijazah bukan tanggal yang valid.');
});

test('import owner can download failed rows as xlsx via signed url', function () {
    $admin = User::factory()->asAdmin()->create();

    $import = Import::query()->create([
        'user_id' => $admin->id,
        'file_name' => 'siswa.csv',
        'file_path' => '/tmp/unused.csv',
        'importer' => StudentImporter::class,
        'total_rows' => 1,
        'processed_rows' => 1,
        'successful_rows' => 0,
        'completed_at' => now(),
    ]);

    FailedImportRow::query()->create([
        'import_id' => $import->id,
        'data' => ['nama' => 'Gagal Impor'],
        'validation_error' => 'Email sudah digunakan.',
    ]);

    $url = URL::signedRoute('personalia.imports.failed-rows.download', [
        'authGuard' => 'web',
        'import' => $import,
    ], absolute: false);

    $this->actingAs($admin)
        ->get($url)
        ->assertOk()
        ->assertDownload('impor-'.$import->id.'-siswa-gagal.xlsx');
});

test('other user cannot download failed rows xlsx', function () {
    $owner = User::factory()->asAdmin()->create();
    $other = User::factory()->asAdmin()->create();

    $import = Import::query()->create([
        'user_id' => $owner->id,
        'file_name' => 'siswa.csv',
        'file_path' => '/tmp/unused.csv',
        'importer' => StudentImporter::class,
        'total_rows' => 1,
        'processed_rows' => 1,
        'successful_rows' => 0,
        'completed_at' => now(),
    ]);

    FailedImportRow::query()->create([
        'import_id' => $import->id,
        'data' => ['nama' => 'Gagal Impor'],
        'validation_error' => 'Email sudah digunakan.',
    ]);

    $url = URL::signedRoute('personalia.imports.failed-rows.download', [
        'authGuard' => 'web',
        'import' => $import,
    ], absolute: false);

    $this->actingAs($other)
        ->get($url)
        ->assertForbidden();
});

test('import teachers modal mounts without error', function () {
    $this->actingAs(User::factory()->asAdmin()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ListTeachers::class)
        ->mountAction('importTeachers')
        ->assertActionMounted('importTeachers');
});
