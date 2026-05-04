<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;

class RaporService
{
    /**
     * Calculate the final rapor score from grade components.
     *
     * Formula: round((avg_ph + avg_tugas + ats + sas) / 4, 2)
     * - avg_ph    = average of non-empty $phScores, 0.0 if empty
     * - avg_tugas = average of non-empty $tugasScores, 0.0 if empty
     *
     * @param  array<int, float>  $phScores
     * @param  array<int, float>  $tugasScores
     */
    public function calculateRaporScore(array $phScores, array $tugasScores, float $ats, float $sas): float
    {
        $avgPh = count($phScores) > 0
            ? array_sum($phScores) / count($phScores)
            : 0.0;

        $avgTugas = count($tugasScores) > 0
            ? array_sum($tugasScores) / count($tugasScores)
            : 0.0;

        return round(($avgPh + $avgTugas + $ats + $sas) / 4, 2);
    }

    /**
     * Assign a grade predicate based on the score.
     *
     * A: score >= 86
     * B: score >= 73
     * C: score >= 60
     * D: score < 60
     */
    public function assignPredicate(float $score): string
    {
        return match (true) {
            $score >= 86.0 => 'A',
            $score >= 73.0 => 'B',
            $score >= 60.0 => 'C',
            default => 'D',
        };
    }

    /**
     * Create or update a Grade record for the given composite key.
     *
     * Uses updateOrCreate() so that duplicate records are never created.
     */
    public function upsertGrade(string $studentId, string $subjectId, string $academicYearId, string $gradeType, float $score): Grade
    {
        return Grade::updateOrCreate(
            [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'academic_year_id' => $academicYearId,
                'grade_type' => $gradeType,
            ],
            ['score' => $score],
        );
    }

    /**
     * Recalculate and persist the RAPOR grade for a student/subject/academicYear.
     *
     * Loads all existing component grades (excluding RAPOR), applies the
     * calculateRaporScore() formula, then upserts a Grade with grade_type = 'RAPOR'.
     */
    public function recalculateRaporScore(string $studentId, string $subjectId, string $academicYearId): Grade
    {
        $grades = Grade::where([
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'academic_year_id' => $academicYearId,
        ])->whereIn('grade_type', array_merge(Grade::PH_TYPES, Grade::TUGAS_TYPES, ['ATS', 'SAS']))->get();

        $phScores = $grades
            ->whereIn('grade_type', Grade::PH_TYPES)
            ->pluck('score')
            ->map(fn ($s) => (float) $s)
            ->values()
            ->all();

        $tugasScores = $grades
            ->whereIn('grade_type', Grade::TUGAS_TYPES)
            ->pluck('score')
            ->map(fn ($s) => (float) $s)
            ->values()
            ->all();

        $ats = (float) ($grades->firstWhere('grade_type', 'ATS')?->score ?? 0.0);
        $sas = (float) ($grades->firstWhere('grade_type', 'SAS')?->score ?? 0.0);

        $raporScore = $this->calculateRaporScore($phScores, $tugasScores, $ats, $sas);

        return $this->upsertGrade($studentId, $subjectId, $academicYearId, 'RAPOR', $raporScore);
    }

    /**
     * Save an array of grade data for a given schedule and academic year.
     *
     * Each item in $gradeData must contain: student_id, grade_type, score.
     * The subject_id is resolved from the Schedule. All upserts and the
     * subsequent RAPOR recalculation run inside a single DB transaction.
     *
     * @param  array<int, array{student_id: string, grade_type: string, score: float}>  $gradeData
     */
    public function saveGrades(array $gradeData, string $scheduleId, string $academicYearId): void
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $subjectId = $schedule->subject_id;

        DB::transaction(function () use ($gradeData, $subjectId, $academicYearId): void {
            $studentIds = [];

            foreach ($gradeData as $item) {
                $this->upsertGrade(
                    $item['student_id'],
                    $subjectId,
                    $academicYearId,
                    $item['grade_type'],
                    (float) $item['score'],
                );

                $studentIds[] = $item['student_id'];
            }

            foreach (array_unique($studentIds) as $studentId) {
                $this->recalculateRaporScore($studentId, $subjectId, $academicYearId);
            }
        });
    }
}
