<?php

namespace App\Filament\Widgets;

use App\Filament\Kepsek\Resources\Attendances\AttendanceResource;
use App\Filament\Kepsek\Resources\Kbms\KbmResource;
use App\Filament\Kepsek\Resources\LessonPlans\LessonPlanResource;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\LessonPlan;
use App\Support\DashboardAcademicContext;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class KepsekOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Dashboard Kepala Sekolah';

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
        return Auth::user()?->role === 'kepala_sekolah';
    }

    protected function getStats(): array
    {
        $ctx = DashboardAcademicContext::statsSuffix();

        $attendanceTodayCount = Attendance::query()
            ->whereHas('kbm', fn ($query) => $query->whereDate('date', today()))
            ->count();

        $kbmTodayCount = Kbm::query()
            ->whereDate('date', today())
            ->count();

        $kbmPendingCount = Kbm::query()
            ->where('status', 'PENDING')
            ->count();

        $lessonPlanPendingCount = LessonPlan::query()
            ->where('status', 'PENDING')
            ->count();

        $latestAnnouncement = Announcement::query()
            ->where(function ($query): void {
                $query->whereJsonContains('target_role', 'kepala_sekolah')
                    ->orWhereJsonContains('target_role', 'super_admin');
            })
            ->orderByDesc('created_at')
            ->first();

        $announcementValue = $latestAnnouncement?->title ?? 'Belum Ada';
        $announcementDescription = $latestAnnouncement
            ? 'Dipublikasikan '.$latestAnnouncement->created_at?->format('d M Y H:i').$ctx
            : 'Belum ada pengumuman untuk kepala sekolah'.$ctx;

        return [
            $this->makeNavigableStat(
                label: 'Kehadiran hari ini',
                value: number_format($attendanceTodayCount),
                description: 'Total entri absensi siswa'.$ctx,
                color: 'success',
                url: AttendanceResource::getUrl(panel: 'kepsek'),
            ),
            $this->makeNavigableStat(
                label: 'KBM hari ini',
                value: number_format($kbmTodayCount),
                description: 'Laporan KBM tercatat untuk hari ini'.$ctx,
                color: 'info',
                url: KbmResource::getUrl(panel: 'kepsek'),
            ),
            $this->makeNavigableStat(
                label: 'KBM menunggu approval',
                value: number_format($kbmPendingCount),
                description: 'Status PENDING — buka daftar Approval KBM'.$ctx,
                color: 'warning',
                url: KbmResource::getUrl(panel: 'kepsek'),
            ),
            $this->makeNavigableStat(
                label: 'RPP menunggu approval',
                value: number_format($lessonPlanPendingCount),
                description: 'Status PENDING — buka daftar Approval RPP'.$ctx,
                color: 'warning',
                url: LessonPlanResource::getUrl(panel: 'kepsek'),
            ),
            $this->makeNavigableStat(
                label: 'Pengumuman terbaru',
                value: $announcementValue,
                description: $announcementDescription,
                color: 'gray',
                url: AnnouncementResource::getUrl(panel: 'kepsek'),
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
