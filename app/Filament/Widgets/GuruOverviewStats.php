<?php

namespace App\Filament\Widgets;

use App\Models\Kbm;
use App\Models\LessonPlan;
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

        $lessonPlanNeedAction = LessonPlan::query()
            ->where('teacher_id', $teacherId)
            ->whereIn('status', ['DRAFT', 'PENDING', 'REVISED'])
            ->count();

        $kbmNeedAction = Kbm::query()
            ->whereHas('schedule', function (Builder $query) use ($teacherId): void {
                $query->where('teacher_id', $teacherId);
            })
            ->whereIn('status', ['DRAFT', 'REVISED'])
            ->count();

        $approvedKbmCount = Kbm::query()
            ->whereHas('schedule', function (Builder $query) use ($teacherId): void {
                $query->where('teacher_id', $teacherId);
            })
            ->where('status', 'APPROVED')
            ->count();

        return [
            Stat::make('Jadwal Hari Ini', number_format($scheduleTodayCount))
                ->description('Total sesi mengajar hari ini')
                ->color('info'),
            Stat::make('RPP Perlu Tindak Lanjut', number_format($lessonPlanNeedAction))
                ->description('Status DRAFT, PENDING, atau REVISED')
                ->color('warning'),
            Stat::make('KBM Perlu Tindak Lanjut', number_format($kbmNeedAction))
                ->description('Laporan perlu dilengkapi atau direvisi')
                ->color('warning'),
            Stat::make('KBM Disetujui', number_format($approvedKbmCount))
                ->description('Laporan yang sudah APPROVED')
                ->color('success'),
        ];
    }
}
