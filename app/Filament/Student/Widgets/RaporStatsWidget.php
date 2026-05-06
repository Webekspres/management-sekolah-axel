<?php

namespace App\Filament\Student\Widgets;

use App\Models\Rapor;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RaporStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $student = Auth::user()?->student;

        if ($student === null) {
            return [
                Stat::make('Total Rapor', '0')->color('gray'),
                Stat::make('Siap Diunduh', '0')->color('gray'),
                Stat::make('Belum Siap', '0')->color('gray'),
            ];
        }

        $rapors = Rapor::where('student_id', $student->id)->get();

        $total = $rapors->count();
        $approved = $rapors->filter(fn (Rapor $rapor): bool => $rapor->isApproved())->count();
        $notReady = $rapors->filter(fn (Rapor $rapor): bool => $rapor->isDraft() || $rapor->isFinalized())->count();

        return [
            Stat::make('Total Rapor', number_format($total))
                ->description('Semua rapor')
                ->color('info'),
            Stat::make('Siap Diunduh', number_format($approved))
                ->description('Rapor dengan status APPROVED')
                ->color('success'),
            Stat::make('Belum Siap', number_format($notReady))
                ->description('Rapor dengan status DRAFT atau FINALIZED')
                ->color($notReady > 0 ? 'warning' : 'success'),
        ];
    }
}
