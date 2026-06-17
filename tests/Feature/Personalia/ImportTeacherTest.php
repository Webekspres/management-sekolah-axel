<?php

use App\Models\City;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Village;
use App\Services\Personalia\TeacherImportService;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;

test('teacher import service creates user teacher and address from row data', function () {
    $province = Province::factory()->create(['name' => 'Jawa Barat Guru']);
    $city = City::factory()->create([
        'province_id' => $province->id,
        'name' => 'Kota Bandung Guru',
    ]);
    $subDistrict = SubDistrict::factory()->create([
        'city_id' => $city->id,
        'name' => 'Coblong Guru',
    ]);
    $village = Village::factory()->create([
        'sub_district_id' => $subDistrict->id,
        'name' => 'Dago Guru',
    ]);

    $row = [
        'nama' => 'Guru Baru',
        'email' => 'guru.baru@import.test',
        'password' => 'password123',
        'jenis_kelamin' => 'P',
        'telepon' => '081234567890',
        'provinsi_lahir' => $province->name,
        'kota_kabupaten_lahir' => $city->name,
        'tanggal_lahir' => '1990-01-01',
        'provinsi' => $province->name,
        'kota_kabupaten' => $city->name,
        'kecamatan' => $subDistrict->name,
        'desa_kelurahan' => $village->name,
        'jalan_nomor' => 'Jl. Mawar 10',
        'kode_pos' => '40135',
        'nip' => '198001012010011001',
        'status_kepegawaian' => 'Guru Kelas',
    ];

    $teacher = app(TeacherImportService::class)->createFromRow($row);

    expect($teacher)->toBeInstanceOf(Teacher::class)
        ->and($teacher->employment_status)->toBe('guru_kelas')
        ->and($teacher->user->email)->toBe('guru.baru@import.test')
        ->and($teacher->user->address_id)->not->toBeNull();
});

test('teacher import service resolves kota bandung separately from kabupaten bandung', function () {
    $province = Province::factory()->create(['name' => 'Jawa Barat']);
    $kotaBandung = City::factory()->create([
        'province_id' => $province->id,
        'name' => 'Kota Bandung',
    ]);
    City::factory()->create([
        'province_id' => $province->id,
        'name' => 'Kabupaten Bandung',
    ]);
    $subDistrict = SubDistrict::factory()->create([
        'city_id' => $kotaBandung->id,
        'name' => 'Coblong',
    ]);
    $village = Village::factory()->create([
        'sub_district_id' => $subDistrict->id,
        'name' => 'Dago',
    ]);

    $row = [
        'nama' => 'Test',
        'email' => 'test@test.com',
        'password' => 'password123',
        'jenis_kelamin' => 'L',
        'telepon' => '081234567890',
        'provinsi_lahir' => $province->name,
        'kota_kabupaten_lahir' => 'Kota Bandung',
        'tanggal_lahir' => '2010-05-15',
        'provinsi' => $province->name,
        'kota_kabupaten' => 'Kota Bandung',
        'kecamatan' => 'Coblong',
        'desa_kelurahan' => 'Dago',
        'jalan_nomor' => 'Jl. Mawar No. 10',
        'kode_pos' => '40135',
        'nip' => '198001012010011001',
        'status_kepegawaian' => 'Guru Kelas',
    ];

    $teacher = app(TeacherImportService::class)->createFromRow($row);

    expect($teacher->user->address->city_id)->toBe($kotaBandung->id);
});

test('teacher import service allows minimal row without optional fields', function () {
    $row = [
        'nama' => 'Guru Minimal Service',
        'email' => 'guru.minimal.service@import.test',
        'password' => 'password123',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '',
        'nip' => '',
        'status_kepegawaian' => '',
    ];

    $teacher = app(TeacherImportService::class)->createFromRow($row);

    expect($teacher)->toBeInstanceOf(Teacher::class)
        ->and($teacher->nip)->toBeNull()
        ->and($teacher->employment_status)->toBeNull()
        ->and($teacher->user->date_of_birth)->toBeNull()
        ->and($teacher->user->address_id)->toBeNull();
});

test('teacher import service rejects duplicate email', function () {
    User::factory()->asGuru()->create(['email' => 'guru.duplikat@import.test']);

    $row = [
        'nama' => 'Guru Duplikat',
        'email' => 'guru.duplikat@import.test',
        'password' => 'password123',
        'jenis_kelamin' => 'L',
        'nip' => '198001012010011002',
        'status_kepegawaian' => 'Staff TU',
    ];

    app(TeacherImportService::class)->createFromRow($row);
})->throws(RowImportFailedException::class);
