<?php

namespace App\Filament\Student\Widgets;

use App\Models\Invoice;
use App\Models\Kbm;
use App\Models\Notification;
use App\Models\Schedule;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SiswaOrtuOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Dashboard Siswa & Orang Tua';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'siswa_ortu';
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $student = $user?->student;

        if ($user === null || $student === null) {
            return [
                Stat::make('Jadwal Hari Ini', '0')
                    ->description('Akun belum terhubung ke data siswa')
                    ->color('gray'),
            ];
        }

        $todayIndex = now()->dayOfWeekIso;

        $scheduleTodayCount = Schedule::query()
            ->where('class_id', $student->class_id)
            ->where('day_of_week', $todayIndex)
            ->count();

        $approvedKbmThisWeek = Kbm::query()
            ->whereHas('schedule', function (Builder $query) use ($student): void {
                $query->where('class_id', $student->class_id);
            })
            ->where('status', 'APPROVED')
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $activeInvoiceCount = Invoice::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['UNPAID', 'PENDING'])
            ->count();

        $unreadNotificationCount = Notification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return [
            Stat::make('Jadwal Hari Ini', number_format($scheduleTodayCount))
                ->description('Sesi belajar sesuai kelas saat ini')
                ->color('info'),
            Stat::make('KBM Approved Minggu Ini', number_format($approvedKbmThisWeek))
                ->description('Hanya laporan yang sudah disetujui Kepsek')
                ->color('success'),
            Stat::make('Tagihan Aktif', number_format($activeInvoiceCount))
                ->description('Status UNPAID dan PENDING')
                ->color('warning'),
            Stat::make('Notifikasi Belum Dibaca', number_format($unreadNotificationCount))
                ->description('Informasi terbaru dari sekolah')
                ->color('danger'),
        ];
    }
}
