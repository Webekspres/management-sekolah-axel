<?php

namespace App\Filament\Student\Widgets;

use App\Services\AttendanceSummaryService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StudentAttendanceSummaryWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Rekap Kehadiran Saya';

    protected static ?int $sort = 5;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 4,
    ];

    /**
     * @var int | array<string, ?int> | null
     */
    protected int|array|null $columns = 2;

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

        $attendances = $student->attendances()->get();

        $service = new AttendanceSummaryService;
        $stats = $service->calculateStats($attendances);

        $percentageColor = $stats['percentage'] >= 75 ? 'success' : 'danger';

        return [
            Stat::make('Total HADIR', number_format($stats['hadir']))
                ->color('success'),
            Stat::make('Total SAKIT', number_format($stats['sakit']))
                ->color('warning'),
            Stat::make('Total IZIN', number_format($stats['izin']))
                ->color('info'),
            Stat::make('Total ALPA', number_format($stats['alpa']))
                ->color('danger'),
            Stat::make('Persentase Kehadiran', $stats['percentage'].'%')
                ->color($percentageColor),
        ];
    }
}
