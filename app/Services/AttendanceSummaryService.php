<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AttendanceSummaryService
{
    public const ATTENDANCE_WARNING_THRESHOLD = 75.0;

    /**
     * Hitung statistik kehadiran dari collection Attendance records.
     *
     * @param  Collection<int, Attendance>  $attendances
     * @return array{total: int, hadir: int, sakit: int, izin: int, alpa: int, percentage: float}
     */
    public function calculateStats(Collection $attendances): array
    {
        $total = $attendances->count();
        $hadir = $attendances->where('status', 'HADIR')->count();
        $sakit = $attendances->where('status', 'SAKIT')->count();
        $izin = $attendances->where('status', 'IZIN')->count();
        $alpa = $attendances->where('status', 'ALPA')->count();

        return [
            'total' => $total,
            'hadir' => $hadir,
            'sakit' => $sakit,
            'izin' => $izin,
            'alpa' => $alpa,
            'percentage' => $this->calculatePercentage($hadir, $total),
        ];
    }

    /**
     * Hitung persentase kehadiran: (HADIR / total) * 100, dibulatkan 1 desimal.
     * Mengembalikan 0.0 jika total = 0.
     */
    public function calculatePercentage(int $hadirCount, int $totalCount): float
    {
        if ($totalCount === 0) {
            return 0.0;
        }

        return round(($hadirCount / $totalCount) * 100, 1);
    }

    /**
     * Tentukan apakah persentase kehadiran di bawah threshold warning (75%).
     */
    public function isBelowWarningThreshold(float $percentage): bool
    {
        return $percentage < self::ATTENDANCE_WARNING_THRESHOLD;
    }

    /**
     * Ambil rekap per siswa untuk satu kelas dalam rentang tanggal tertentu.
     *
     * @return Collection<int, array{student: Student, stats: array}>
     */
    public function getClassSummary(SchoolClass $class, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        return $class->students()
            ->with(['attendances' => function ($query) use ($from, $to): void {
                $query->whereHas('kbm', function ($q) use ($from, $to): void {
                    if ($from !== null) {
                        $q->whereDate('date', '>=', $from);
                    }
                    if ($to !== null) {
                        $q->whereDate('date', '<=', $to);
                    }
                });
            }])
            ->get()
            ->map(fn (Student $student): array => [
                'student' => $student,
                'stats' => $this->calculateStats($student->attendances),
            ]);
    }

    /**
     * Ambil rekap kehadiran satu siswa dalam rentang tanggal tertentu.
     *
     * @return array{student: Student, stats: array, attendances: Collection}
     */
    public function getStudentSummary(Student $student, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $attendances = $student->attendances()
            ->whereHas('kbm', function ($query) use ($from, $to): void {
                if ($from !== null) {
                    $query->whereDate('date', '>=', $from);
                }
                if ($to !== null) {
                    $query->whereDate('date', '<=', $to);
                }
            })
            ->with('kbm')
            ->get();

        return [
            'student' => $student,
            'stats' => $this->calculateStats($attendances),
            'attendances' => $attendances,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Metode baru untuk rekap absensi rapor
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kembalikan daftar bulan untuk semester tertentu.
     *
     * Semester 1: Juli–Desember (7–12)
     * Semester 2: Januari–Juni (1–6)
     *
     * @return array<int>
     */
    public function getSemesterMonths(int $semester): array
    {
        return $semester === 1
            ? [7, 8, 9, 10, 11, 12]
            : [1, 2, 3, 4, 5, 6];
    }

    /**
     * Ambil rekap absensi per bulan per mata pelajaran untuk satu siswa.
     *
     * Mengembalikan Collection dengan key = subject_id, value = array berisi:
     * - subject_name: string
     * - months: array<int, array{hadir: int, sakit: int, izin: int, alpa: int, total: int}>
     * - total: int (total semua sesi)
     *
     * @return Collection<string, array{subject_name: string, months: array, total: int}>
     */
    public function getMonthlyBreakdownBySubject(Student $student, AcademicYear $academicYear): Collection
    {
        $semesterMonths = $this->getSemesterMonths((int) $academicYear->semester);

        $attendances = Attendance::where('student_id', $student->id)
            ->whereHas('kbm', fn ($query) => $this->whereKbmDateInSemesterMonths($query, $semesterMonths))
            ->with(['kbm.schedule.subject'])
            ->get();

        return $attendances
            ->groupBy(fn (Attendance $a) => $a->kbm?->schedule?->subject_id)
            ->filter(fn ($group, $subjectId) => $subjectId !== null)
            ->map(function (Collection $group, string $subjectId) use ($semesterMonths): array {
                $subject = $group->first()->kbm?->schedule?->subject;

                $months = [];
                foreach ($semesterMonths as $month) {
                    $monthGroup = $group->filter(
                        fn (Attendance $a) => $a->kbm?->date?->month === $month
                    );

                    $months[$month] = [
                        'hadir' => $monthGroup->where('status', 'HADIR')->count(),
                        'sakit' => $monthGroup->where('status', 'SAKIT')->count(),
                        'izin' => $monthGroup->where('status', 'IZIN')->count(),
                        'alpa' => $monthGroup->where('status', 'ALPA')->count(),
                        'total' => $monthGroup->count(),
                    ];
                }

                return [
                    'subject_name' => $subject?->name ?? '—',
                    'months' => $months,
                    'total' => $group->count(),
                ];
            });
    }

    /**
     * Hitung total SAKIT, IZIN, ALPA untuk satu siswa dalam satu tahun akademik.
     *
     * @return array{sakit: int, izin: int, alpa: int, total: int}
     */
    public function getOverallSummary(Student $student, AcademicYear $academicYear): array
    {
        $semesterMonths = $this->getSemesterMonths((int) $academicYear->semester);

        $attendances = Attendance::where('student_id', $student->id)
            ->whereHas('kbm', fn ($query) => $this->whereKbmDateInSemesterMonths($query, $semesterMonths))
            ->get();

        return [
            'sakit' => $attendances->where('status', 'SAKIT')->count(),
            'izin' => $attendances->where('status', 'IZIN')->count(),
            'alpa' => $attendances->where('status', 'ALPA')->count(),
            'total' => $attendances->count(),
        ];
    }

    /**
     * @param  Builder<Kbm>  $query
     * @param  array<int, int>  $semesterMonths
     */
    private function whereKbmDateInSemesterMonths(Builder $query, array $semesterMonths): void
    {
        $query->where(function (Builder $monthQuery) use ($semesterMonths): void {
            foreach ($semesterMonths as $month) {
                $monthQuery->orWhereMonth('date', $month);
            }
        });
    }
}
