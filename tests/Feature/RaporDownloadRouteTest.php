<?php

use App\Models\Rapor;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
});

test('super admin can download rapor via route', function (): void {
    $admin = User::factory()->asAdmin()->create();
    $rapor = Rapor::factory()->approved()->create([
        'file_path' => 'rapors/test.pdf',
    ]);
    Storage::put($rapor->file_path, 'pdf-content');

    $this->actingAs($admin)
        ->get(route('rapor.download', $rapor))
        ->assertOk();
});

test('kepala sekolah can download rapor via route', function (): void {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $rapor = Rapor::factory()->approved()->create([
        'file_path' => 'rapors/test.pdf',
    ]);
    Storage::put($rapor->file_path, 'pdf-content');

    $this->actingAs($kepsek)
        ->get(route('rapor.download', $rapor))
        ->assertOk();
});

test('wali kelas guru can download rapor for their class student via route', function (): void {
    $guruUser = User::factory()->asGuru()->create();
    $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
    $schoolClass = SchoolClass::factory()->create(['teacher_id' => $teacher->id]);
    $student = Student::factory()->create(['class_id' => $schoolClass->id]);
    $rapor = Rapor::factory()->finalized()->create([
        'student_id' => $student->id,
        'file_path' => 'rapors/test.pdf',
    ]);
    Storage::put($rapor->file_path, 'pdf-content');

    $this->actingAs($guruUser)
        ->get(route('rapor.download', $rapor))
        ->assertOk();
});

test('siswa can download own approved rapor via route', function (): void {
    $siswaUser = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $siswaUser->id]);
    $rapor = Rapor::factory()->approved()->create([
        'student_id' => $student->id,
        'file_path' => 'rapors/test.pdf',
    ]);
    Storage::put($rapor->file_path, 'pdf-content');

    $this->actingAs($siswaUser)
        ->get(route('rapor.download', $rapor))
        ->assertOk();
});

test('siswa cannot download draft rapor via route', function (): void {
    $siswaUser = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $siswaUser->id]);
    $rapor = Rapor::factory()->create([
        'student_id' => $student->id,
        'status' => 'DRAFT',
        'file_path' => 'rapors/test.pdf',
    ]);
    Storage::put($rapor->file_path, 'pdf-content');

    $this->actingAs($siswaUser)
        ->get(route('rapor.download', $rapor))
        ->assertForbidden();
});

test('unauthorized guru cannot download rapor via route', function (): void {
    $guruUser = User::factory()->asGuru()->create();
    Teacher::factory()->create(['user_id' => $guruUser->id]);
    $rapor = Rapor::factory()->approved()->create([
        'file_path' => 'rapors/test.pdf',
    ]);
    Storage::put($rapor->file_path, 'pdf-content');

    $this->actingAs($guruUser)
        ->get(route('rapor.download', $rapor))
        ->assertForbidden();
});

test('rapor download route returns 404 when file missing', function (): void {
    $admin = User::factory()->asAdmin()->create();
    $rapor = Rapor::factory()->approved()->create([
        'file_path' => 'rapors/missing.pdf',
    ]);

    $this->actingAs($admin)
        ->get(route('rapor.download', $rapor))
        ->assertNotFound();
});
