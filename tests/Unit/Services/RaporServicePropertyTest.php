<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use App\Services\RaporService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// Feature: assessment-module, Property 1: Kalkulasi Nilai Rapor
// Validates: Requirements 4.1, 4.2, 4.3, 19.8
test('Property 1: Rapor_Score equals formula for all valid grade combinations', function (): void {
    $service = app(RaporService::class);

    for ($i = 0; $i < propertyIterationCount(); $i++) {
        // Generate partial PH scores (0–4 scores, each optional)
        $phScores = array_filter(
            array_map(fn () => fake()->optional(0.7)->randomFloat(2, 0, 100), range(1, 4)),
            fn ($v) => $v !== null,
        );

        // Generate partial TUGAS scores (0–4 scores, each optional)
        $tugasScores = array_filter(
            array_map(fn () => fake()->optional(0.7)->randomFloat(2, 0, 100), range(1, 4)),
            fn ($v) => $v !== null,
        );

        // ATS and SAS: optional (null treated as 0.0)
        $atsRaw = fake()->optional(0.7)->randomFloat(2, 0, 100);
        $sasRaw = fake()->optional(0.7)->randomFloat(2, 0, 100);
        $ats = $atsRaw ?? 0.0;
        $sas = $sasRaw ?? 0.0;

        // Compute expected value using the spec formula
        $avgPh = count($phScores) > 0 ? array_sum($phScores) / count($phScores) : 0.0;
        $avgTugas = count($tugasScores) > 0 ? array_sum($tugasScores) / count($tugasScores) : 0.0;
        $expected = round(($avgPh + $avgTugas + $ats + $sas) / 4, 2);

        $actual = $service->calculateRaporScore(array_values($phScores), array_values($tugasScores), $ats, $sas);

        expect($actual)->toBe($expected, sprintf(
            'Iteration %d failed: phScores=[%s], tugasScores=[%s], ats=%s, sas=%s — expected %s, got %s',
            $i + 1,
            implode(',', $phScores),
            implode(',', $tugasScores),
            $ats,
            $sas,
            $expected,
            $actual,
        ));
    }
});

// Feature: assessment-module, Property 2: Determinisme Predikat Nilai
// Validates: Requirements 6.2, 19.9
test('Property 2: Grade predicate is deterministic and consistent with defined ranges', function (): void {
    $service = app(RaporService::class);

    for ($i = 0; $i < propertyIterationCount(); $i++) {
        $score = fake()->randomFloat(2, 0, 100);

        // Determine expected predicate based on spec ranges
        $expected = match (true) {
            $score >= 86.0 => 'A',
            $score >= 73.0 => 'B',
            $score >= 60.0 => 'C',
            default => 'D',
        };

        $firstCall = $service->assignPredicate($score);
        $secondCall = $service->assignPredicate($score);

        // Verify correct predicate for the range
        expect($firstCall)->toBe($expected, sprintf(
            'Iteration %d: score=%s — expected predicate %s, got %s',
            $i + 1,
            $score,
            $expected,
            $firstCall,
        ));

        // Verify idempotence: same input always yields same output
        expect($secondCall)->toBe($firstCall, sprintf(
            'Iteration %d: score=%s — predicate is not idempotent (first=%s, second=%s)',
            $i + 1,
            $score,
            $firstCall,
            $secondCall,
        ));
    }
});

// Feature: assessment-module, Property 3: Upsert Grade tidak membuat duplikat
// Validates: Requirements 17.4, 17.5
test('Property 3: Upsert Grade does not create duplicate records for any composite key', function (): void {
    $service = app(RaporService::class);

    for ($i = 0; $i < propertyIterationCount(); $i++) {
        $student = Student::factory()->create();
        $subject = Subject::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        // Pick a random grade_type from PH_TYPES + ATS + SAS
        $gradeType = fake()->randomElement(array_merge(Grade::PH_TYPES, ['ATS', 'SAS']));

        $score1 = fake()->randomFloat(2, 0, 100);
        $score2 = fake()->randomFloat(2, 0, 100);

        // First upsert — creates the record
        $service->upsertGrade($student->id, $subject->id, $academicYear->id, $gradeType, $score1);

        // Second upsert with same composite key but different score — must update, not insert
        $service->upsertGrade($student->id, $subject->id, $academicYear->id, $gradeType, $score2);

        $compositeKey = [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'grade_type' => $gradeType,
        ];

        // Assert exactly 1 record exists (no duplicate)
        $count = Grade::where($compositeKey)->count();
        expect($count)->toBe(1, sprintf(
            'Iteration %d: expected 1 Grade record for grade_type=%s, found %d',
            $i + 1,
            $gradeType,
            $count,
        ));

        // Assert the stored score equals the SECOND call's score (last write wins)
        $storedScore = (float) Grade::where($compositeKey)->value('score');
        expect($storedScore)->toBe((float) $score2, sprintf(
            'Iteration %d: grade_type=%s — expected stored score %s (second write), got %s',
            $i + 1,
            $gradeType,
            $score2,
            $storedScore,
        ));
    }
});

// Feature: assessment-module, Property 4: Konsistensi Nilai Rapor setelah setiap simpan
// Validates: Requirements 4.4, 4.6
test('Property 4: Rapor score is always consistent with formula after any save sequence', function (): void {
    $service = app(RaporService::class);

    for ($i = 0; $i < propertyIterationCount(); $i++) {
        $student = Student::factory()->create();
        $subject = Subject::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        // Save a random non-empty subset of grade types (from PH1, PH2, PH3, ATS, SAS)
        $allTypes = ['PH1', 'PH2', 'PH3', 'ATS', 'SAS'];
        $count = fake()->numberBetween(1, count($allTypes));
        $typesToSave = fake()->randomElements($allTypes, $count);

        foreach ($typesToSave as $gradeType) {
            $service->upsertGrade(
                $student->id,
                $subject->id,
                $academicYear->id,
                $gradeType,
                fake()->randomFloat(2, 0, 100),
            );
        }

        // Recalculate the RAPOR score
        $service->recalculateRaporScore($student->id, $subject->id, $academicYear->id);

        // Assert a RAPOR grade record exists
        $raporGrade = Grade::where([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'grade_type' => 'RAPOR',
        ])->first();

        expect($raporGrade)->not->toBeNull(sprintf(
            'Iteration %d: expected a RAPOR grade record to exist after recalculate',
            $i + 1,
        ));

        // Compute expected score from saved components using the formula
        $savedGrades = Grade::where([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
        ])->whereIn('grade_type', array_merge(Grade::PH_TYPES, Grade::TUGAS_TYPES, ['ATS', 'SAS']))->get();

        $phScores = $savedGrades->whereIn('grade_type', Grade::PH_TYPES)->pluck('score')->map(fn ($s) => (float) $s)->values()->all();
        $tugasScores = $savedGrades->whereIn('grade_type', Grade::TUGAS_TYPES)->pluck('score')->map(fn ($s) => (float) $s)->values()->all();
        $ats = (float) ($savedGrades->firstWhere('grade_type', 'ATS')?->score ?? 0.0);
        $sas = (float) ($savedGrades->firstWhere('grade_type', 'SAS')?->score ?? 0.0);

        $expectedScore = $service->calculateRaporScore($phScores, $tugasScores, $ats, $sas);

        // Assert the RAPOR score matches calculateRaporScore() applied to the saved components
        expect((float) $raporGrade->score)->toBe($expectedScore, sprintf(
            'Iteration %d: RAPOR score mismatch — expected %s from formula, got %s',
            $i + 1,
            $expectedScore,
            $raporGrade->score,
        ));
    }
});
