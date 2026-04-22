<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Kbm;
use App\Models\LessonPlan;
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
        $pendingLessonPlans = LessonPlan::query()
            ->where('status', 'PENDING')
            ->count();

        $pendingKbms = Kbm::query()
            ->where('status', 'PENDING')
            ->count();

        $approvedKbmsThisMonth = Kbm::query()
            ->where('status', 'APPROVED')
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $unpaidInvoices = Invoice::query()
            ->whereIn('status', ['UNPAID', 'PENDING'])
            ->count();

        return [
            Stat::make('RPP Menunggu Approval', number_format($pendingLessonPlans))
                ->description('Perlu verifikasi sebelum publikasi')
                ->color('warning'),
            Stat::make('KBM Menunggu Approval', number_format($pendingKbms))
                ->description('Laporan harian menunggu persetujuan')
                ->color('warning'),
            Stat::make('KBM Disetujui Bulan Ini', number_format($approvedKbmsThisMonth))
                ->description('Status APPROVED pada bulan berjalan')
                ->color('success'),
            Stat::make('Tagihan Belum Lunas', number_format($unpaidInvoices))
                ->description('Monitoring administrasi keuangan')
                ->color('danger'),
        ];
    }
}
