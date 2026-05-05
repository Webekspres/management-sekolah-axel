<?php

namespace App\Filament\Widgets;

use App\Filament\Clusters\Academic\Resources\Schedules\ScheduleResource;
use App\Filament\Guru\Resources\Attendances\AttendanceResource;
use App\Filament\Guru\Resources\Kbms\KbmResource;
use App\Filament\Guru\Resources\LessonPlans\LessonPlanResource;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\LessonPlan;
use App\Models\Schedule;
use App\Support\DashboardAcademicContext;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GuruOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Dashboard Guru';

    protected ?string $pollingInterval = null;

    protected static ?int $sort = -1;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = 'full';

    /**
     * @var int | array<string, ?int> | null
     */
    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'lg' => 3,
    ];

    public static function canView(): bool
    {
        return Auth::user()?->role === 'guru';
    }

    protected function getStats(): array
    {
        $ctx = DashboardAcademicContext::statsSuffix();
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

        $attendanceTodayCount = Attendance::query()
            ->whereHas('kbm.schedule', function (Builder $query) use ($teacherId): void {
                $query->where('teacher_id', $teacherId);
            })
            ->whereHas('kbm', fn (Builder $query) => $query->whereDate('date', today()))
            ->count();

        $hadirCount = Attendance::query()
            ->whereHas('kbm.schedule', function (Builder $query) use ($teacherId): void {
                $query->where('teacher_id', $teacherId);
            })
            ->whereHas('kbm', fn (Builder $query) => $query->whereDate('date', today()))
            ->where('status', 'HADIR')
            ->count();

        $pctHadir = $attendanceTodayCount > 0 ? (int) round(100 * $hadirCount / $attendanceTodayCount) : 0;

        $latestKbmValue = $latestKbm?->status ?? 'Belum Ada';
        $latestKbmDescription = $latestKbm
            ? 'Laporan tanggal '.$latestKbm->date->format('d M Y').$ctx
            : 'Belum ada laporan KBM'.$ctx;

        $draftLp = LessonPlan::query()->where('teacher_id', $teacherId)->where('status', 'DRAFT')->count();
        $pendingLp = LessonPlan::query()->where('teacher_id', $teacherId)->where('status', 'PENDING')->count();
        $approvedLp = LessonPlan::query()->where('teacher_id', $teacherId)->where('status', 'APPROVED')->count();

        return [
            $this->makeNavigableStat(
                label: 'Jadwal Hari Ini',
                value: number_format($scheduleTodayCount),
                description: 'Total sesi mengajar hari ini'.$ctx,
                color: 'info',
                url: ScheduleResource::getUrl(panel: 'guru'),
            ),
            $this->makeNavigableStat(
                label: 'KBM Terbaru',
                value: $latestKbmValue,
                description: $latestKbmDescription,
                color: 'warning',
                url: KbmResource::getUrl(panel: 'guru'),
            ),
            $this->makeNavigableStat(
                label: 'Ringkasan Absensi Kelas',
                value: number_format($attendanceTodayCount),
                description: "Entri hari ini — HADIR: {$hadirCount} ({$pctHadir}%){$ctx}",
                color: 'success',
                url: AttendanceResource::getUrl(panel: 'guru'),
            ),
            $this->makeNavigableStat(
                label: 'RPP saya (draft)',
                value: number_format($draftLp),
                description: 'Belum diajukan'.$ctx,
                color: 'gray',
                url: LessonPlanResource::getUrl(panel: 'guru'),
            ),
            $this->makeNavigableStat(
                label: 'RPP menunggu kepsek',
                value: number_format($pendingLp),
                description: 'Status PENDING'.$ctx,
                color: 'warning',
                url: LessonPlanResource::getUrl(panel: 'guru'),
            ),
            $this->makeNavigableStat(
                label: 'RPP disetujui',
                value: number_format($approvedLp),
                description: 'Status APPROVED'.$ctx,
                color: 'success',
                url: LessonPlanResource::getUrl(panel: 'guru'),
            ),
        ];
    }

    private function makeNavigableStat(
        string $label,
        string $value,
        string $description,
        string $color,
        ?string $url,
    ): Stat {
        $stat = Stat::make($label, $value)
            ->description($description)
            ->color($color);

        if ($url === null) {
            return $stat;
        }

        return $stat
            ->descriptionIcon('heroicon-m-arrow-right-circle')
            ->extraAttributes([
                'class' => 'cursor-pointer',
                'onclick' => "window.location.href = '{$url}'",
            ]);
    }
}
