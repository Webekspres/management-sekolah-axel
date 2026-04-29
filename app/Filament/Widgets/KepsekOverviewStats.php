<?php

namespace App\Filament\Widgets;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Kbm;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class KepsekOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Dashboard Kepala Sekolah';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'kepala_sekolah';
    }

    protected function getStats(): array
    {
        $attendanceTodayCount = Attendance::query()
            ->whereHas('kbm', fn ($query) => $query->whereDate('date', today()))
            ->count();

        $kbmTodayCount = Kbm::query()
            ->whereDate('date', today())
            ->count();

        $kbmPendingCount = Kbm::query()
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
            ? 'Dipublikasikan '.$latestAnnouncement->created_at?->format('d M Y H:i')
            : 'Belum ada pengumuman untuk kepala sekolah';

        return [
            Stat::make('Overview Kehadiran', number_format($attendanceTodayCount))
                ->description('Total entri kehadiran hari ini')
                ->color('warning'),
            Stat::make('Overview KBM', number_format($kbmTodayCount))
                ->description('KBM hari ini, pending: '.$kbmPendingCount)
                ->color('warning'),
            Stat::make('Pengumuman Terbaru', $announcementValue)
                ->description($announcementDescription)
                ->color('success'),
        ];
    }
}
