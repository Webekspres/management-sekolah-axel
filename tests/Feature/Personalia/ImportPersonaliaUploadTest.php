<?php

use App\Filament\Clusters\DataPersonalia\Resources\Students\Pages\ListStudents;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\Pages\ListTeachers;
use App\Filament\Imports\StudentImporter;
use App\Filament\Imports\TeacherImporter;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Import\XlsxToCsvConverter;
use Filament\Actions\Imports\ImportColumn;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

beforeEach(function () {
    $this->actingAs(User::factory()->asAdmin()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/**
 * @param  array<int, array<string, string>>  $rows
 */
function buildPersonaliaCsv(array $rows): string
{
    $headers = array_keys($rows[0]);

    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, $headers);

    foreach ($rows as $row) {
        fputcsv($handle, array_values($row));
    }

    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    return $csv;
}

/**
 * @param  array<int, ImportColumn>  $columns
 * @param  list<string>  $csvHeaders
 * @return array<string, string|null>
 */
function buildPersonaliaColumnMap(array $columns, array $csvHeaders): array
{
    return collect($columns)
        ->mapWithKeys(fn ($column) => [
            $column->getName() => in_array($column->getName(), $csvHeaders, true) ? $column->getName() : null,
        ])
        ->all();
}

test('unggah data siswa membuat record student melalui import action', function () {
    $level = Level::factory()->create();
    $class = SchoolClass::factory()->create([
        'level_id' => $level->id,
        'name' => 'VII A',
    ]);

    session(['active_academic_level_id' => $level->id]);

    $csv = buildPersonaliaCsv([
        [
            'nama' => 'Siswa Impor Test',
            'email' => 'siswa.impor@example.test',
            'password' => 'password123',
            'jenis_kelamin' => 'L',
            'kelas' => 'VII A',
            'nipd' => '12345',
            'nik' => '3201234567890001',
            'nisn' => '0051234567',
        ],
    ]);

    $columnMap = buildPersonaliaColumnMap(StudentImporter::getColumns(), [
        'nama', 'email', 'password', 'jenis_kelamin', 'kelas', 'nipd', 'nik', 'nisn',
    ]);

    Livewire::test(ListStudents::class)
        ->callAction('importStudents', [
            'file' => UploadedFile::fake()->createWithContent('siswa.csv', $csv),
            'columnMap' => $columnMap,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $student = Student::query()->where('nipd', '12345')->first();

    expect($student)->not->toBeNull()
        ->and($student->class_id)->toBe($class->id)
        ->and(User::query()->where('email', 'siswa.impor@example.test')->exists())->toBeTrue();
});

test('unggah data siswa dari file excel membuat record student', function () {
    $level = Level::factory()->create();
    $class = SchoolClass::factory()->create([
        'level_id' => $level->id,
        'name' => 'VIII B',
    ]);

    session(['active_academic_level_id' => $level->id]);

    $xlsxPath = sys_get_temp_dir().'/unggah-siswa-'.uniqid('', true).'.xlsx';

    $writer = new Writer;
    $writer->openToFile($xlsxPath);
    $writer->getCurrentSheet()->setName(XlsxToCsvConverter::DATA_SHEET_NAME);

    $headers = ['nama', 'email', 'password', 'jenis_kelamin', 'kelas', 'nipd', 'nik', 'nisn'];
    $writer->addRow(Row::fromValues($headers));
    $writer->addRow(Row::fromValues([
        'Siswa Excel Test',
        'siswa.excel@example.test',
        'password123',
        'P',
        'VIII B',
        '54321',
        '3201234567890003',
        '0057654321',
    ]));
    $writer->close();

    $columnMap = buildPersonaliaColumnMap(StudentImporter::getColumns(), $headers);

    Livewire::test(ListStudents::class)
        ->callAction('importStudents', [
            'file' => UploadedFile::fake()->createWithContent('siswa.xlsx', file_get_contents($xlsxPath)),
            'columnMap' => $columnMap,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    @unlink($xlsxPath);

    $student = Student::query()->where('nipd', '54321')->first();

    expect($student)->not->toBeNull()
        ->and($student->class_id)->toBe($class->id);
});

test('unggah data siswa tanpa tingkat sekolah dihentikan dengan notifikasi error', function () {
    session(['active_academic_level_id' => null]);

    $csv = buildPersonaliaCsv([
        [
            'nama' => 'Siswa Tanpa Level',
            'email' => 'siswa.tanpa.level@example.test',
            'password' => 'password123',
            'jenis_kelamin' => 'L',
            'kelas' => 'VII A',
            'nipd' => '99999',
            'nik' => '3201234567890002',
            'nisn' => '0059999999',
        ],
    ]);

    $columnMap = buildPersonaliaColumnMap(StudentImporter::getColumns(), [
        'nama', 'email', 'password', 'jenis_kelamin', 'kelas', 'nipd', 'nik', 'nisn',
    ]);

    Livewire::test(ListStudents::class)
        ->callAction('importStudents', [
            'file' => UploadedFile::fake()->createWithContent('siswa.csv', $csv),
            'columnMap' => $columnMap,
        ])
        ->assertNotified(__('personalia.import.errors.pre_validation_title'));

    expect(Student::query()->where('nipd', '99999')->exists())->toBeFalse();
});

test('unggah data guru membuat record teacher melalui import action', function () {
    $csv = buildPersonaliaCsv([
        [
            'nama' => 'Guru Impor Test',
            'email' => 'guru.impor@example.test',
            'password' => 'password123',
            'jenis_kelamin' => 'P',
        ],
    ]);

    $columnMap = buildPersonaliaColumnMap(TeacherImporter::getColumns(), [
        'nama', 'email', 'password', 'jenis_kelamin',
    ]);

    Livewire::test(ListTeachers::class)
        ->callAction('importTeachers', [
            'file' => UploadedFile::fake()->createWithContent('guru.csv', $csv),
            'columnMap' => $columnMap,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect(User::query()->where('email', 'guru.impor@example.test')->exists())->toBeTrue()
        ->and(Teacher::query()->whereHas('user', fn ($query) => $query->where('email', 'guru.impor@example.test'))->exists())->toBeTrue();
});
