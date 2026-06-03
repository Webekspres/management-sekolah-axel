<?php

use App\Models\City;
use App\Models\Province;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\Personalia\StudentImportService;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;

test('student import service creates user and student from row data', function () {
    $class = SchoolClass::factory()->create(['name' => '7 A Import']);
    $province = Province::factory()->create(['name' => 'Jawa Barat Import']);
    $city = City::factory()->create([
        'province_id' => $province->id,
        'name' => 'Kota Bandung Import',
    ]);

    $row = [
        'nama' => 'Siswa Baru',
        'email' => 'siswa.baru@import.test',
        'password' => 'password123',
        'jenis_kelamin' => 'L',
        'telepon' => '081234567890',
        'provinsi_lahir' => $province->name,
        'kota_kabupaten_lahir' => $city->name,
        'tanggal_lahir' => '2010-05-15',
        'kelas' => $class->name,
        'nipd' => '2024990001',
        'nisn' => '0099887766',
        'nik' => '3273010105100001',
    ];

    $student = app(StudentImportService::class)->createFromRow($row, [
        'academic_level_id' => $class->level_id,
    ]);

    expect($student)->toBeInstanceOf(Student::class)
        ->and($student->nipd)->toBe('2024990001')
        ->and($student->user->email)->toBe('siswa.baru@import.test')
        ->and($student->user->role)->toBe('siswa_ortu');
});

test('student import service rejects duplicate email', function () {
    $class = SchoolClass::factory()->create(['name' => '7 B Import']);
    $existing = User::factory()->asSiswa()->create(['email' => 'duplikat@import.test']);

    Student::factory()->create([
        'user_id' => $existing->id,
        'class_id' => $class->id,
    ]);

    $row = [
        'nama' => 'Siswa Duplikat',
        'email' => 'duplikat@import.test',
        'password' => 'password123',
        'jenis_kelamin' => 'L',
        'kelas' => $class->name,
        'nipd' => '2024990002',
        'nisn' => '0099887767',
        'nik' => '3273010105100002',
    ];

    app(StudentImportService::class)->createFromRow($row, [
        'academic_level_id' => $class->level_id,
    ]);
})->throws(RowImportFailedException::class);
