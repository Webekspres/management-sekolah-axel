<?php

namespace App\Filament\Student\Widgets;

use App\Models\Rapor;
use App\Support\DashboardAcademicContext;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RaporStatsWidget extends StatsOverviewWidget
{
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
        'sm' => 3,
    ];

    public static function canView(): bool
    {
        return Auth::user()?->role === 'siswa_ortu';
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $student = $user?->student;

        if ($student === null) {
            return [
                Stat::make('Informasi', 'Akun belum terhubung ke data siswa')
                    ->color('gray'),
            ];
        }

        $rapors = Rapor::query()
            ->where('student_id', $student->id)
            ->get();

        $total = $rapors->count();
        $approvedCount = $rapors->filter(fn (Rapor $rapor): bool => $rapor->isApproved())->count();
        $notReadyCount = $rapors->filter(
            fn (Rapor $rapor): bool => $rapor->isDraft() || $rapor->isFinalized()
        )->count();

        $ctx = DashboardAcademicContext::statsSuffix();

        return [
            Stat::make('Total Rapor', number_format($total))
                ->description('Semua rapor Anda'.$ctx)
                ->color('info'),
            Stat::make('Siap Diunduh', number_format($approvedCount))
                ->description('Rapor dengan status disetujui'.$ctx)
                ->color('success'),
            Stat::make('Belum Siap', number_format($notReadyCount))
                ->description('Rapor draft atau terfinalisasi'.$ctx)
                ->color($notReadyCount > 0 ? 'warning' : 'success'),
        ];
    }
}
