<?php

namespace App\Filament\Kepsek\Widgets;

use App\Models\Attendance;
use App\Support\DashboardAcademicContext;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class KepsekWeeklyAttendanceChart extends ChartWidget
{
    protected ?string $heading = 'Trend kehadiran sekolah';

    protected ?string $description = 'Hadir vs tidak hadir per minggu (8 minggu terakhir)';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '16rem';

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 6,
    ];

    protected static ?int $sort = 13;

    public static function canView(): bool
    {
        return Auth::user()?->role === 'kepala_sekolah';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $labels = [];
        $hadir = [];
        $tidakHadir = [];

        for ($w = 7; $w >= 0; $w--) {
            $anchor = now()->subWeeks($w)->copy()->startOfWeek();
            $start = $anchor->toDateString();
            $end = $anchor->copy()->endOfWeek()->toDateString();

            $labels[] = $anchor->translatedFormat('d M');

            $base = Attendance::query()
                ->whereHas('kbm', fn ($q) => $q->whereBetween('date', [$start, $end]));

            $hadir[] = (clone $base)->where('status', 'HADIR')->count();
            $tidakHadir[] = (clone $base)->whereIn('status', ['IZIN', 'SAKIT', 'ALPA'])->count();
        }

        $this->description = 'Hadir vs tidak hadir per minggu (8 minggu terakhir)'.DashboardAcademicContext::statsSuffix();

        return [
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $hadir,
                    'backgroundColor' => '#22c55e',
                ],
                [
                    'label' => 'Tidak hadir',
                    'data' => $tidakHadir,
                    'backgroundColor' => '#f97316',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
