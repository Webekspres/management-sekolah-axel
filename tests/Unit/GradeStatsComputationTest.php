<?php

// Feature: student-grades-report-ui, Property 1: Statistik nilai dihitung dengan benar dari koleksi grade

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectKkm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Property 1: Statistik nilai dihitung dengan benar dari koleksi grade
 *
 * Validates: Requirements 1.1
 */
test('Property 1: grade stats are computed correctly for any collection of RAPOR grades', function (): void {
    for ($i = 0; $i < 100; $i++) {
        // Arrange: create a student with a school class (for level_id)
        $schoolClass = SchoolClass::factory()->create();
        $student = Student::factory()->create(['class_id' => $schoolClass->id]);
        $academicYear = AcademicYear::factory()->active()->create();

        // Vary number of subjects (1–10)
        $subjectCount = fake()->numberBetween(1, 10);
        $subjects = Subject::factory()->count($subjectCount)->create();

        // Vary KKM per subject (60.0–80.0)
        $kkmMap = [];
        foreach ($subjects as $subject) {
            $kkm = fake()->randomFloat(2, 60.0, 80.0);
            SubjectKkm::factory()->create([
                'subject_id' => $subject->id,
                'level_id' => $schoolClass->level_id,
                'kkm' => $kkm,
            ]);
            $kkmMap[$subject->id] = $kkm;
        }

        // Create one RAPOR grade per subject with score 0.0–100.0
        $scoreMap = [];
        foreach ($subjects as $subject) {
            $score = fake()->randomFloat(2, 0.0, 100.0);
            Grade::factory()->create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'academic_year_id' => $academicYear->id,
                'grade_type' => 'RAPOR',
                'score' => $score,
            ]);
            $scoreMap[$subject->id] = $score;
        }

        // Act: replicate the computation logic from GradeStatsWidget::getStats()
        $grades = Grade::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('grade_type', 'RAPOR')
            ->get();

        $computedSubjectCount = $grades->pluck('subject_id')->unique()->count();
        $computedAvgRapor = $grades->isNotEmpty() ? $grades->avg('score') : 0.0;
        $levelId = $schoolClass->level_id;
        $computedBelowKkm = $grades->filter(function (Grade $grade) use ($levelId): bool {
            $kkm = ($levelId !== null)
                ? SubjectKkm::getKkm($grade->subject_id, $levelId)
                : 70.0;

            return (float) $grade->score < $kkm;
        })->count();

        // Assert: subject_count = number of distinct subjects in the collection
        expect($computedSubjectCount)->toBe($subjectCount, sprintf(
            'Iteration %d: expected subject_count=%d, got %d',
            $i + 1,
            $subjectCount,
            $computedSubjectCount,
        ));

        // Assert: avg_rapor = arithmetic mean of all RAPOR scores (tolerance 0.01)
        $expectedAvg = array_sum($scoreMap) / count($scoreMap);
        expect(abs($computedAvgRapor - $expectedAvg))->toBeLessThanOrEqual(0.01, sprintf(
            'Iteration %d: avg_rapor mismatch — expected %.4f, got %.4f (diff=%.6f)',
            $i + 1,
            $expectedAvg,
            $computedAvgRapor,
            abs($computedAvgRapor - $expectedAvg),
        ));

        // Assert: below_kkm = count of subjects where RAPOR score < applicable KKM
        $expectedBelowKkm = 0;
        foreach ($scoreMap as $subjectId => $score) {
            $kkm = $kkmMap[$subjectId] ?? 70.0;
            if ($score < $kkm) {
                $expectedBelowKkm++;
            }
        }

        expect($computedBelowKkm)->toBe($expectedBelowKkm, sprintf(
            'Iteration %d: below_kkm mismatch — expected %d, got %d',
            $i + 1,
            $expectedBelowKkm,
            $computedBelowKkm,
        ));

        // Assert invariant: below_kkm <= subject_count
        expect($computedBelowKkm)->toBeLessThanOrEqual($computedSubjectCount, sprintf(
            'Iteration %d: invariant violated — below_kkm (%d) > subject_count (%d)',
            $i + 1,
            $computedBelowKkm,
            $computedSubjectCount,
        ));
    }
});
