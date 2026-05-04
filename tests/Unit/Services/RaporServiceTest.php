<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use App\Services\RaporService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// calculateRaporScore()
// ─────────────────────────────────────────────────────────────────────────────

test('calculateRaporScore returns correct value with all components present', function (): void {
    // PH1–PH4, TUGAS1–TUGAS4, ATS, SAS all provided
    $phScores = [80.0, 90.0, 70.0, 100.0]; // avg = 85.0
    $tugasScores = [75.0, 85.0, 95.0, 65.0];  // avg = 80.0
    $ats = 88.0;
    $sas = 92.0;

    // Rapor_Score = round((85.0 + 80.0 + 88.0 + 92.0) / 4, 2) = round(345.0 / 4, 2) = 86.25
    $expected = round((85.0 + 80.0 + 88.0 + 92.0) / 4, 2);

    expect(app(RaporService::class)->calculateRaporScore($phScores, $tugasScores, $ats, $sas))
        ->toBe($expected);
});

test('calculateRaporScore handles partial PH scores (only PH1 and PH2)', function (): void {
    // Only PH1 and PH2 provided — avg of 2 values
    $phScores = [80.0, 60.0]; // avg = 70.0
    $tugasScores = [90.0, 80.0, 70.0, 60.0]; // avg = 75.0
    $ats = 85.0;
    $sas = 75.0;

    // Rapor_Score = round((70.0 + 75.0 + 85.0 + 75.0) / 4, 2) = round(305.0 / 4, 2) = 76.25
    $expected = round((70.0 + 75.0 + 85.0 + 75.0) / 4, 2);

    expect(app(RaporService::class)->calculateRaporScore($phScores, $tugasScores, $ats, $sas))
        ->toBe($expected);
});

test('calculateRaporScore treats missing ATS and SAS as 0', function (): void {
    $phScores = [80.0, 90.0, 70.0, 100.0]; // avg = 85.0
    $tugasScores = [75.0, 85.0, 95.0, 65.0];  // avg = 80.0
    $ats = 0.0;
    $sas = 0.0;

    // Rapor_Score = round((85.0 + 80.0 + 0.0 + 0.0) / 4, 2) = round(165.0 / 4, 2) = 41.25
    $expected = round((85.0 + 80.0 + 0.0 + 0.0) / 4, 2);

    expect(app(RaporService::class)->calculateRaporScore($phScores, $tugasScores, $ats, $sas))
        ->toBe($expected);
});

test('calculateRaporScore returns 0 when no scores are provided at all', function (): void {
    // No PH, no TUGAS, ATS = 0, SAS = 0
    // avg_ph = 0, avg_tugas = 0
    // Rapor_Score = round((0 + 0 + 0 + 0) / 4, 2) = 0.0
    expect(app(RaporService::class)->calculateRaporScore([], [], 0.0, 0.0))
        ->toBe(0.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// assignPredicate()
// ─────────────────────────────────────────────────────────────────────────────

test('assignPredicate returns A for scores in range 86–100', function (): void {
    $service = app(RaporService::class);

    expect($service->assignPredicate(86.0))->toBe('A');
    expect($service->assignPredicate(95.0))->toBe('A');
    expect($service->assignPredicate(100.0))->toBe('A');
});

test('assignPredicate returns B for scores in range 73–85', function (): void {
    $service = app(RaporService::class);

    expect($service->assignPredicate(73.0))->toBe('B');
    expect($service->assignPredicate(79.5))->toBe('B');
    expect($service->assignPredicate(85.0))->toBe('B');
});

test('assignPredicate returns C for scores in range 60–72', function (): void {
    $service = app(RaporService::class);

    expect($service->assignPredicate(60.0))->toBe('C');
    expect($service->assignPredicate(66.0))->toBe('C');
    expect($service->assignPredicate(72.0))->toBe('C');
});

test('assignPredicate returns D for scores below 60', function (): void {
    $service = app(RaporService::class);

    expect($service->assignPredicate(59.99))->toBe('D');
    expect($service->assignPredicate(30.0))->toBe('D');
    expect($service->assignPredicate(0.0))->toBe('D');
});

test('assignPredicate handles boundary value 86.0 as A', function (): void {
    expect(app(RaporService::class)->assignPredicate(86.0))->toBe('A');
});

test('assignPredicate handles boundary value 85.99 as B', function (): void {
    expect(app(RaporService::class)->assignPredicate(85.99))->toBe('B');
});

test('assignPredicate handles boundary value 73.0 as B', function (): void {
    expect(app(RaporService::class)->assignPredicate(73.0))->toBe('B');
});

test('assignPredicate handles boundary value 72.99 as C', function (): void {
    expect(app(RaporService::class)->assignPredicate(72.99))->toBe('C');
});

test('assignPredicate handles boundary value 60.0 as C', function (): void {
    expect(app(RaporService::class)->assignPredicate(60.0))->toBe('C');
});

test('assignPredicate handles boundary value 59.99 as D', function (): void {
    expect(app(RaporService::class)->assignPredicate(59.99))->toBe('D');
});

// ─────────────────────────────────────────────────────────────────────────────
// upsertGrade()
// ─────────────────────────────────────────────────────────────────────────────

test('upsertGrade creates a new Grade record when none exists', function (): void {
    $student = Student::factory()->create();
    $subject = Subject::factory()->create();
    $academicYear = AcademicYear::factory()->create();

    $grade = app(RaporService::class)->upsertGrade(
        $student->id,
        $subject->id,
        $academicYear->id,
        'PH1',
        85.0,
    );

    expect($grade)->toBeInstanceOf(Grade::class);

    expect(Grade::where([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'PH1',
    ])->count())->toBe(1);

    expect((float) Grade::where([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'PH1',
    ])->value('score'))->toBe(85.0);
});

test('upsertGrade updates existing record without creating a duplicate', function (): void {
    $student = Student::factory()->create();
    $subject = Subject::factory()->create();
    $academicYear = AcademicYear::factory()->create();

    $service = app(RaporService::class);

    // First call — creates the record
    $service->upsertGrade($student->id, $subject->id, $academicYear->id, 'ATS', 70.0);

    // Second call with same composite key but different score — should update, not insert
    $service->upsertGrade($student->id, $subject->id, $academicYear->id, 'ATS', 90.0);

    $count = Grade::where([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'ATS',
    ])->count();

    expect($count)->toBe(1);

    $score = (float) Grade::where([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'ATS',
    ])->value('score');

    expect($score)->toBe(90.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// recalculateRaporScore()
// ─────────────────────────────────────────────────────────────────────────────

test('recalculateRaporScore saves a Grade record with grade_type RAPOR', function (): void {
    $student = Student::factory()->create();
    $subject = Subject::factory()->create();
    $academicYear = AcademicYear::factory()->create();

    $service = app(RaporService::class);

    // Seed some component grades first
    $service->upsertGrade($student->id, $subject->id, $academicYear->id, 'PH1', 80.0);
    $service->upsertGrade($student->id, $subject->id, $academicYear->id, 'ATS', 75.0);
    $service->upsertGrade($student->id, $subject->id, $academicYear->id, 'SAS', 85.0);

    $raporGrade = $service->recalculateRaporScore($student->id, $subject->id, $academicYear->id);

    expect($raporGrade)->toBeInstanceOf(Grade::class);
    expect($raporGrade->grade_type)->toBe('RAPOR');

    expect(Grade::where([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'RAPOR',
    ])->count())->toBe(1);
});

test('recalculateRaporScore updates the RAPOR grade value after component grades change', function (): void {
    $student = Student::factory()->create();
    $subject = Subject::factory()->create();
    $academicYear = AcademicYear::factory()->create();

    $service = app(RaporService::class);

    // Initial component grades: PH1=80, ATS=70, SAS=90
    // avg_ph=80, avg_tugas=0, ats=70, sas=90 → (80+0+70+90)/4 = 60.0
    $service->upsertGrade($student->id, $subject->id, $academicYear->id, 'PH1', 80.0);
    $service->upsertGrade($student->id, $subject->id, $academicYear->id, 'ATS', 70.0);
    $service->upsertGrade($student->id, $subject->id, $academicYear->id, 'SAS', 90.0);
    $service->recalculateRaporScore($student->id, $subject->id, $academicYear->id);

    $firstScore = (float) Grade::where([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'RAPOR',
    ])->value('score');

    // Update PH1 to 100 — new rapor: (100+0+70+90)/4 = 65.0
    $service->upsertGrade($student->id, $subject->id, $academicYear->id, 'PH1', 100.0);
    $service->recalculateRaporScore($student->id, $subject->id, $academicYear->id);

    $updatedScore = (float) Grade::where([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'RAPOR',
    ])->value('score');

    // Score should have changed
    expect($updatedScore)->not->toBe($firstScore);

    // Still only one RAPOR record
    expect(Grade::where([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'RAPOR',
    ])->count())->toBe(1);

    // Verify the updated value matches the formula: (100+0+70+90)/4 = 65.0
    expect($updatedScore)->toBe(65.0);
});
