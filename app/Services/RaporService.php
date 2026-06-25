<?php

namespace App\Services;

use App\Models\AttitudeScore;
use App\Models\Grade;
use App\Models\KnowledgeSkillScore;
use App\Models\LearningAchievement;
use App\Models\PersonalityScore;
use App\Models\Rapor;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\SubjectKkm;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
     * Ensure a Rapor record exists for the given student and academic year.
     * Creates one with status DRAFT if it doesn't exist yet.
     */
    public function ensureRaporExists(string $studentId, string $academicYearId): Rapor
    {
        return Rapor::firstOrCreate(
            [
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
            ],
            [
                'status' => 'DRAFT',
                'file_path' => null,
                'approved_at' => null,
                'rejection_note' => null,
            ],
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
        // Ensure a Rapor record exists for this student/academic year
        $this->ensureRaporExists($studentId, $academicYearId);

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

    // ─────────────────────────────────────────────────────────────────────────
    // Rapor Workflow
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validate that all required grade components are present for a rapor.
     *
     * Returns an array of missing component descriptions.
     * An empty array means the rapor is complete and ready to finalize.
     *
     * @return array<string>
     */
    public function validateCompleteness(Rapor $rapor): array
    {
        $missing = [];
        $studentId = $rapor->student_id;
        $academicYearId = $rapor->academic_year_id;

        // Check at least one PH grade exists across all subjects
        $hasPh = Grade::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('grade_type', Grade::PH_TYPES)
            ->exists();

        if (! $hasPh) {
            $missing[] = 'Nilai Penilaian Harian (PH) belum diisi';
        }

        // Check ATS exists for at least one subject
        $hasAts = Grade::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('grade_type', 'ATS')
            ->exists();

        if (! $hasAts) {
            $missing[] = 'Nilai ATS belum diisi';
        }

        // Check SAS exists for at least one subject
        $hasSas = Grade::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('grade_type', 'SAS')
            ->exists();

        if (! $hasSas) {
            $missing[] = 'Nilai SAS belum diisi';
        }

        // Check knowledge/skill scores exist
        $hasKnowledgeSkill = KnowledgeSkillScore::withoutGlobalScope('academic_level')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->exists();

        if (! $hasKnowledgeSkill) {
            $missing[] = 'Nilai Pengetahuan & Keterampilan belum diisi';
        }

        // Check attitude scores exist
        $hasAttitude = AttitudeScore::withoutGlobalScope('academic_level')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->exists();

        if (! $hasAttitude) {
            $missing[] = 'Nilai Sikap belum diisi';
        }

        // Check personality score exists
        $hasPersonality = PersonalityScore::withoutGlobalScope('academic_level')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->exists();

        if (! $hasPersonality) {
            $missing[] = 'Nilai Kepribadian belum diisi';
        }

        return $missing;
    }

    /**
     * Finalize a rapor: change status from DRAFT to FINALIZED.
     *
     * @throws \RuntimeException if rapor is not in DRAFT status
     */
    public function finalizeRapor(Rapor $rapor, string $program, string $sumberPembelajaran): void
    {
        if (! $rapor->isDraft()) {
            throw new \RuntimeException("Rapor hanya bisa difinalisasi dari status DRAFT. Status saat ini: {$rapor->status}");
        }

        $rapor->update([
            'program' => $program,
            'sumber_pembelajaran' => $sumberPembelajaran,
            'status' => 'FINALIZED',
        ]);
    }

    /**
     * Approve a rapor: change status from FINALIZED to APPROVED.
     *
     * @throws \RuntimeException if rapor is not in FINALIZED status
     */
    public function approveRapor(Rapor $rapor): void
    {
        if (! $rapor->isFinalized()) {
            throw new \RuntimeException("Rapor hanya bisa di-approve dari status FINALIZED. Status saat ini: {$rapor->status}");
        }

        $rapor->update([
            'status' => 'APPROVED',
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject a rapor: revert from FINALIZED back to DRAFT with a rejection note.
     *
     * @throws \RuntimeException if rapor is not in FINALIZED status
     */
    public function rejectRapor(Rapor $rapor, string $rejectionNote): void
    {
        if (! $rapor->isFinalized()) {
            throw new \RuntimeException("Rapor hanya bisa di-reject dari status FINALIZED. Status saat ini: {$rapor->status}");
        }

        $rapor->update([
            'status' => 'DRAFT',
            'rejection_note' => $rejectionNote,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PDF Generation
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate a PDF rapor for the given Rapor record.
     *
     * Loads all required data in ≤5 queries using eager loading.
     * Saves the file to storage and updates file_path in the rapors table.
     *
     * @throws \RuntimeException if rapor is APPROVED (cannot regenerate)
     * @throws \RuntimeException if PDF generation fails
     */
    public function generatePdf(Rapor $rapor): string
    {
        if ($rapor->isApproved()) {
            throw new \RuntimeException('Rapor yang sudah APPROVED tidak dapat digenerate ulang. Kembalikan ke DRAFT terlebih dahulu.');
        }

        try {
            // Query 1: Load student with class, user, and school class
            $rapor->load([
                'student.user',
                'student.schoolClass.homeroomTeacher.user',
                'academicYear',
            ]);

            $student = $rapor->student;
            $academicYear = $rapor->academicYear;
            $schoolClass = $student?->schoolClass;

            // Query 2: Load all grades for this student/academic year
            $grades = Grade::where('student_id', $student->id)
                ->where('academic_year_id', $academicYear->id)
                ->with('subject')
                ->get();

            // Query 3: Load attitude, knowledge/skill, learning achievements, personality
            $attitudeScores = AttitudeScore::withoutGlobalScope('academic_level')
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYear->id)
                ->get();

            $knowledgeSkillScores = KnowledgeSkillScore::withoutGlobalScope('academic_level')
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYear->id)
                ->with('subject')
                ->get()
                ->map(function ($ks) use ($schoolClass) {
                    $ks->kkm = $this->resolveKkm($schoolClass, $ks->subject_id);

                    return $ks;
                });

            $learningAchievements = LearningAchievement::withoutGlobalScope('academic_level')
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYear->id)
                ->with('subject')
                ->get();

            $personalityScore = PersonalityScore::withoutGlobalScope('academic_level')
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYear->id)
                ->first();

            // Query 4: Attendance summary
            $attendanceSummaryService = app(AttendanceSummaryService::class);
            $semesterMonths = $attendanceSummaryService->getSemesterMonths((int) $academicYear->semester);
            $attendanceBySubject = $attendanceSummaryService->getMonthlyBreakdownBySubject($student, $academicYear);
            $overallAttendance = $attendanceSummaryService->getOverallSummary($student, $academicYear);

            // Build grades by subject map
            $gradesBySubject = $grades
                ->groupBy('subject_id')
                ->map(function ($subjectGrades) {
                    $subject = $subjectGrades->first()->subject;
                    $gradeMap = $subjectGrades->keyBy('grade_type');

                    // Get teacher name from schedule
                    $schedule = Schedule::where('subject_id', $subject?->id)
                        ->with('teacher.user')
                        ->first();

                    return [
                        'subject_name' => $subject?->name ?? '—',
                        'grades' => $gradeMap->map(fn ($g) => (string) $g->score)->all(),
                        'teacher_name' => $schedule?->teacher?->user?->name ?? '—',
                    ];
                })
                ->all();

            $monthNames = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
            ];

            $waliKelasName = $schoolClass?->homeroomTeacher?->user?->name;

            $titimangsaRaw = Setting::where('key', 'titimangsa')->value('value');
            $titimangsaFormatted = $titimangsaRaw
                ? 'Jakarta, '.Carbon::parse($titimangsaRaw)->locale('id')->isoFormat('D MMMM YYYY')
                : '—';

            $pdf = Pdf::loadView('rapor.pdf_v2', compact(
                'rapor',
                'student',
                'academicYear',
                'schoolClass',
                'grades',
                'gradesBySubject',
                'attitudeScores',
                'knowledgeSkillScores',
                'learningAchievements',
                'personalityScore',
                'attendanceBySubject',
                'overallAttendance',
                'semesterMonths',
                'monthNames',
                'waliKelasName',
                'titimangsaFormatted',
            ))->setPaper('a4', 'portrait');

            $filePath = "rapors/{$rapor->id}.pdf";
            Storage::put($filePath, $pdf->output());

            $rapor->update([
                'file_path' => $filePath,
                'generated_at' => now(),
            ]);

            return $filePath;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('RaporService: gagal generate PDF', [
                'rapor_id' => $rapor->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Gagal generate PDF rapor: {$e->getMessage()}", 0, $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KKM Resolution
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resolve the applicable KKM for a subject in a given school class.
     *
     * Priority:
     *   1. schoolClass->kkm if not null (KKM per kelas)
     *   2. SubjectKkm::getKkm($subjectId, $levelId) (KKM per mata pelajaran per jenjang)
     *   3. 70.0 as final fallback (handled inside SubjectKkm::getKkm)
     */
    public function resolveKkm(?SchoolClass $schoolClass, string $subjectId): float
    {
        if ($schoolClass !== null && $schoolClass->kkm !== null) {
            return (float) $schoolClass->kkm;
        }

        $levelId = $schoolClass?->level_id;

        if ($levelId !== null) {
            return SubjectKkm::getKkm($subjectId, $levelId);
        }

        return 70.0;
    }
}
