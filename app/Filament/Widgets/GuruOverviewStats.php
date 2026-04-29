<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Schedule;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GuruOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Dashboard Guru';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'guru';
    }

    protected function getStats(): array
    {
        $teacherId = Auth::user()?->teacher?->id;

        if ($teacherId === null) {
            return [
                Stat::make('Jadwal Hari Ini', '0')
                    ->description('Akun belum terhubung ke profil guru')
                    ->color('gray'),
            ];
        }

        $todayIndex = now()->dayOfWeekIso;

        $scheduleTodayCount = Schedule::query()
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $todayIndex)
            ->count();

        $latestKbm = Kbm::query()
            ->whereHas('schedule', function (Builder $query) use ($teacherId): void {
                $query->where('teacher_id', $teacherId);
            })
            ->orderByDesc('date')
            ->first();

        $attendanceTodayQuery = Kbm::query()
            ->whereHas('schedule', function (Builder $query) use ($teacherId): void {
                $query->where('teacher_id', $teacherId);
            })
            ->whereDate('date', today())
            ->withCount('attendances');

        $attendanceTodayCount = $attendanceTodayQuery->get()->sum('attendances_count');
        $hadirCount = Attendance::query()
            ->whereHas('kbm.schedule', function (Builder $query) use ($teacherId): void {
                $query->where('teacher_id', $teacherId);
            })
            ->whereHas('kbm', fn (Builder $query) => $query->whereDate('date', today()))
            ->where('status', 'HADIR')
            ->count();

        $latestKbmValue = $latestKbm?->status ?? 'Belum Ada';
        $latestKbmDescription = $latestKbm
            ? 'Laporan tanggal '.$latestKbm->date->format('d M Y')
            : 'Belum ada laporan KBM';

        return [
            Stat::make('Jadwal Hari Ini', number_format($scheduleTodayCount))
                ->description('Total sesi mengajar hari ini')
                ->color('info'),
            Stat::make('KBM Terbaru', $latestKbmValue)
                ->description($latestKbmDescription)
                ->color('warning'),
            Stat::make('Ringkasan Absensi Kelas', number_format($attendanceTodayCount))
                ->description("Entri hari ini, HADIR: {$hadirCount}")
                ->color('success'),
        ];
    }
}
