<?php

use App\Models\AcademicYear;
use App\Models\Rapor;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\RaporPolicy;
use App\Services\RaporService;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Storage::fake('local');

    $this->admin = User::factory()->asAdmin()->create();
    $this->academicYear = AcademicYear::factory()->active()->create();
    $this->subject = Subject::factory()->create();

    $waliKelasUser = User::factory()->asGuru()->create();
    $waliKelasTeacher = Teacher::factory()->create(['user_id' => $waliKelasUser->id]);
    $this->schoolClass = SchoolClass::factory()->create([
        'teacher_id' => $waliKelasTeacher->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    $this->student = Student::factory()->create([
        'class_id' => $this->schoolClass->id,
    ]);

    $this->rapor = Rapor::factory()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'status' => 'DRAFT',
    ]);

    $this->service = app(RaporService::class);

    actingAs($this->admin);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: PDF berhasil digenerate dan file_path tersimpan
// ─────────────────────────────────────────────────────────────────────────────

test('PDF berhasil digenerate dan file_path tersimpan di tabel rapors', function () {
    $filePath = $this->service->generatePdf($this->rapor);

    expect($filePath)->not->toBeNull();
    expect($filePath)->toBeString();
    expect(Storage::exists($filePath))->toBeTrue();

    $this->rapor->refresh();
    expect($this->rapor->file_path)->toBe($filePath);

    assertDatabaseHas(Rapor::class, [
        'id' => $this->rapor->id,
        'file_path' => $filePath,
    ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Regenerasi PDF menimpa file lama (DRAFT/FINALIZED)
// ─────────────────────────────────────────────────────────────────────────────

test('regenerasi PDF menimpa file lama untuk rapor DRAFT', function () {
    // Generate first time
    $firstPath = $this->service->generatePdf($this->rapor);
    expect(Storage::exists($firstPath))->toBeTrue();

    // Generate second time
    $secondPath = $this->service->generatePdf($this->rapor);

    // Should be same path (overwritten)
    expect($secondPath)->toBe($firstPath);
    expect(Storage::exists($secondPath))->toBeTrue();
});

test('regenerasi PDF menimpa file lama untuk rapor FINALIZED', function () {
    $finalizedRapor = Rapor::factory()->finalized()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    $firstPath = $this->service->generatePdf($finalizedRapor);
    $secondPath = $this->service->generatePdf($finalizedRapor);

    expect($secondPath)->toBe($firstPath);
    expect(Storage::exists($secondPath))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: PDF tidak dapat digenerate ulang untuk rapor APPROVED
// ─────────────────────────────────────────────────────────────────────────────

test('PDF tidak dapat digenerate ulang untuk rapor APPROVED', function () {
    $approvedRapor = Rapor::factory()->approved()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'file_path' => 'rapors/existing.pdf',
    ]);

    // Should throw exception or return existing path without regenerating
    expect(fn () => $this->service->generatePdf($approvedRapor))
        ->toThrow(RuntimeException::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Siswa dapat download rapor APPROVED
// ─────────────────────────────────────────────────────────────────────────────

test('siswa dapat download rapor APPROVED miliknya', function () {
    $siswaUser = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $siswaUser->id]);

    $filePath = 'rapors/'.$student->id.'.pdf';
    Storage::put($filePath, 'fake pdf content');

    $approvedRapor = Rapor::factory()->approved()->create([
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'file_path' => $filePath,
    ]);

    // Policy check: siswa can download their own APPROVED rapor
    $policy = app(RaporPolicy::class);
    expect($policy->download($siswaUser, $approvedRapor))->toBeTrue();
    expect(Storage::exists($filePath))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Siswa tidak dapat download rapor DRAFT/FINALIZED
// ─────────────────────────────────────────────────────────────────────────────

test('siswa tidak dapat download rapor DRAFT atau FINALIZED', function () {
    $siswaUser = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $siswaUser->id]);

    $draftRapor = Rapor::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'status' => 'DRAFT',
    ]);

    $finalizedRapor = Rapor::factory()->finalized()->create([
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    $policy = app(RaporPolicy::class);
    expect($policy->download($siswaUser, $draftRapor))->toBeFalse();
    expect($policy->download($siswaUser, $finalizedRapor))->toBeFalse();
});
