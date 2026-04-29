<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
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
}
