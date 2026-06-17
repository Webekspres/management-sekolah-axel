<?php

use App\Models\AcademicYear;
use App\Models\AttitudeScore;
use App\Models\Grade;
use App\Models\KnowledgeSkillScore;
use App\Models\PersonalityScore;
use App\Models\Rapor;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\RaporPolicy;
use App\Services\RaporService;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

// ─────────────────────────────────────────────────────────────────────────────
// Setup
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->academicYear = AcademicYear::factory()->active()->create();
    $this->subject = Subject::factory()->create();

    $this->waliKelasUser = User::factory()->asGuru()->create();
    $this->waliKelasTeacher = Teacher::factory()->create(['user_id' => $this->waliKelasUser->id]);

    $this->schoolClass = SchoolClass::factory()->create([
        'teacher_id' => $this->waliKelasTeacher->id,
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

    $this->kepsekUser = User::factory()->asKepalaSekolah()->create();
    $this->service = app(RaporService::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: validateCompleteness — rapor lengkap
// ─────────────────────────────────────────────────────────────────────────────

test('validateCompleteness mengembalikan array kosong jika semua komponen lengkap', function () {
    createCompleteGrades($this->student, $this->subject, $this->academicYear);

    $missing = $this->service->validateCompleteness($this->rapor);

    expect($missing)->toBeEmpty();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: validateCompleteness — komponen kurang
// ─────────────────────────────────────────────────────────────────────────────

test('validateCompleteness mengembalikan daftar komponen yang kurang', function () {
    // No grades at all
    $missing = $this->service->validateCompleteness($this->rapor);

    expect($missing)->not->toBeEmpty();
    expect(count($missing))->toBeGreaterThan(0);
});

test('validateCompleteness mendeteksi PH yang kurang', function () {
    // Create everything except PH
    Grade::factory()->create([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'ATS',
        'score' => 75.00,
    ]);
    Grade::factory()->create([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_type' => 'SAS',
        'score' => 78.00,
    ]);
    KnowledgeSkillScore::factory()->create([
        'student_id' => $this->student->id,
        'subject_id' => $this->subject->id,
        'academic_year_id' => $this->academicYear->id,
    ]);
    AttitudeScore::factory()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);
    PersonalityScore::factory()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    $missing = $this->service->validateCompleteness($this->rapor);

    expect($missing)->toContain('Nilai Penilaian Harian (PH) belum diisi');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: finalizeRapor — DRAFT → FINALIZED
// ─────────────────────────────────────────────────────────────────────────────

test('wali kelas dapat finalisasi rapor yang lengkap (DRAFT → FINALIZED)', function () {
    createCompleteGrades($this->student, $this->subject, $this->academicYear);

    actingAs($this->waliKelasUser);

    $this->service->finalizeRapor($this->rapor, 'Reguler', 'Buku teks dan modul sekolah');

    $this->rapor->refresh();
    expect($this->rapor->status)->toBe('FINALIZED');
    expect($this->rapor->isFinalized())->toBeTrue();
    expect($this->rapor->program)->toBe('Reguler');
    expect($this->rapor->sumber_pembelajaran)->toBe('Buku teks dan modul sekolah');

    assertDatabaseHas(Rapor::class, [
        'id' => $this->rapor->id,
        'status' => 'FINALIZED',
        'program' => 'Reguler',
        'sumber_pembelajaran' => 'Buku teks dan modul sekolah',
    ]);
});

test('finalizeRapor gagal jika rapor bukan DRAFT', function () {
    $finalizedRapor = Rapor::factory()->finalized()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    expect(fn () => $this->service->finalizeRapor($finalizedRapor, 'Reguler', 'Modul'))
        ->toThrow(RuntimeException::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: approveRapor — FINALIZED → APPROVED
// ─────────────────────────────────────────────────────────────────────────────

test('kepsek dapat approve rapor FINALIZED (FINALIZED → APPROVED)', function () {
    $finalizedRapor = Rapor::factory()->finalized()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    actingAs($this->kepsekUser);

    $this->service->approveRapor($finalizedRapor);

    $finalizedRapor->refresh();
    expect($finalizedRapor->status)->toBe('APPROVED');
    expect($finalizedRapor->isApproved())->toBeTrue();
    expect($finalizedRapor->approved_at)->not->toBeNull();
});

test('approveRapor gagal jika rapor bukan FINALIZED', function () {
    expect(fn () => $this->service->approveRapor($this->rapor))
        ->toThrow(RuntimeException::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: rejectRapor — FINALIZED → DRAFT dengan catatan
// ─────────────────────────────────────────────────────────────────────────────

test('reject rapor mempertahankan program dan sumber pembelajaran', function () {
    $finalizedRapor = Rapor::factory()->finalized()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'program' => 'Akselerasi',
        'sumber_pembelajaran' => 'Modul daring',
    ]);

    $this->service->rejectRapor($finalizedRapor, 'Perlu revisi');

    $finalizedRapor->refresh();
    expect($finalizedRapor->program)->toBe('Akselerasi');
    expect($finalizedRapor->sumber_pembelajaran)->toBe('Modul daring');
});

test('kepsek dapat reject rapor FINALIZED (FINALIZED → DRAFT dengan catatan)', function () {
    $finalizedRapor = Rapor::factory()->finalized()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    actingAs($this->kepsekUser);

    $this->service->rejectRapor($finalizedRapor, 'Nilai ATS perlu dikoreksi');

    $finalizedRapor->refresh();
    expect($finalizedRapor->status)->toBe('DRAFT');
    expect($finalizedRapor->isDraft())->toBeTrue();
    expect($finalizedRapor->rejection_note)->toBe('Nilai ATS perlu dikoreksi');

    assertDatabaseHas(Rapor::class, [
        'id' => $finalizedRapor->id,
        'status' => 'DRAFT',
        'rejection_note' => 'Nilai ATS perlu dikoreksi',
    ]);
});

test('rejectRapor gagal jika rapor bukan FINALIZED', function () {
    expect(fn () => $this->service->rejectRapor($this->rapor, 'catatan'))
        ->toThrow(RuntimeException::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Wali Kelas dapat revert FINALIZED ke DRAFT (sebelum di-approve)
// ─────────────────────────────────────────────────────────────────────────────

test('wali kelas dapat revert rapor FINALIZED ke DRAFT sebelum di-approve', function () {
    $finalizedRapor = Rapor::factory()->finalized()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    actingAs($this->waliKelasUser);

    // Wali kelas can revert using rejectRapor (or a dedicated revert method)
    // The policy allows wali kelas to update finalized rapor back to draft
    $this->service->rejectRapor($finalizedRapor, 'Direvisi oleh Wali Kelas');

    $finalizedRapor->refresh();
    expect($finalizedRapor->status)->toBe('DRAFT');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Guru tidak dapat edit nilai setelah rapor FINALIZED
// ─────────────────────────────────────────────────────────────────────────────

test('rapor FINALIZED mencegah modifikasi nilai oleh guru', function () {
    $finalizedRapor = Rapor::factory()->finalized()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    // Policy check: guru cannot update a finalized rapor
    $policy = app(RaporPolicy::class);

    $otherGuru = User::factory()->asGuru()->create();
    Teacher::factory()->create(['user_id' => $otherGuru->id]);

    // Even wali kelas cannot "update" a finalized rapor via policy
    // (finalize/revert is a separate action, not update)
    expect($policy->update($this->waliKelasUser, $finalizedRapor))->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test: Rapor APPROVED tidak bisa dimodifikasi
// ─────────────────────────────────────────────────────────────────────────────

test('rapor APPROVED tidak bisa di-finalize ulang', function () {
    $approvedRapor = Rapor::factory()->approved()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    expect(fn () => $this->service->finalizeRapor($approvedRapor, 'Reguler', 'Modul'))
        ->toThrow(RuntimeException::class);
});

test('rapor APPROVED tidak bisa di-reject', function () {
    $approvedRapor = Rapor::factory()->approved()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    expect(fn () => $this->service->rejectRapor($approvedRapor, 'catatan'))
        ->toThrow(RuntimeException::class);
});
