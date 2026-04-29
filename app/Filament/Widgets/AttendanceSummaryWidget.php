<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Student;
use App\Services\AttendanceSummaryService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AttendanceSummaryWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Absensi';

    public static function canView(): bool
    {
        $role = Auth::user()?->role;

        return in_array($role, ['guru', 'kepala_sekolah'], true);
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $role = $user?->role;
        $teacherId = $user?->teacher?->id;

        if ($role === 'guru' && $teacherId === null) {
            return [
                Stat::make('Ringkasan Absensi', '-')
                    ->description('Akun belum terhubung ke profil guru')
                    ->color('gray'),
            ];
        }

        // Stat 1: Total Absensi Hari Ini
        $todayAttendanceQuery = Attendance::query()
            ->whereHas('kbm', fn ($q) => $q->whereDate('date', today()));

        if ($role === 'guru' && $teacherId) {
            $todayAttendanceQuery->whereHas('kbm.schedule', fn ($q) => $q->where('teacher_id', $teacherId));
        }

        $todayTotal = $todayAttendanceQuery->count();

        // Stat 2: Total HADIR Hari Ini
        $todayHadirQuery = Attendance::query()
            ->where('status', 'HADIR')
            ->whereHas('kbm', fn ($q) => $q->whereDate('date', today()));

        if ($role === 'guru' && $teacherId) {
            $todayHadirQuery->whereHas('kbm.schedule', fn ($q) => $q->where('teacher_id', $teacherId));
        }

        $todayHadir = $todayHadirQuery->count();

        // Stat 3: Siswa dengan kehadiran < 75%
        $belowThresholdCount = Student::withoutGlobalScopes()
            ->whereHas('attendances')
            ->get()
            ->filter(function (Student $student) use ($role, $teacherId): bool {
                $query = $student->attendances();

                if ($role === 'guru' && $teacherId) {
                    $query->whereHas('kbm.schedule', fn ($q) => $q->where('teacher_id', $teacherId));
                }

                $attendances = $query->get();

                if ($attendances->isEmpty()) {
                    return false;
                }

                $hadir = $attendances->where('status', 'HADIR')->count();
                $total = $attendances->count();

                return ($hadir / $total) * 100 < AttendanceSummaryService::ATTENDANCE_WARNING_THRESHOLD;
            })
            ->count();

        return [
            Stat::make('Total Absensi Hari Ini', number_format($todayTotal))
                ->description('Semua status kehadiran hari ini')
                ->color('info'),
            Stat::make('Total HADIR Hari Ini', number_format($todayHadir))
                ->description('Siswa hadir hari ini')
                ->color('success'),
            Stat::make('Siswa Kehadiran < 75%', number_format($belowThresholdCount))
                ->description('Perlu perhatian khusus')
                ->color('danger'),
        ];
    }
}
