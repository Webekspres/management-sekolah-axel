<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Support\DashboardAcademicContext;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class AdminAttendanceTrendChart extends ChartWidget
{
    protected ?string $heading = 'Trend entri absensi';

    protected ?string $description = 'Hadir vs tidak hadir (I+S+A) per hari';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '16rem';

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 20;

    public ?string $filter = '7d';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    protected function getFilters(): ?array
    {
        return [
            '7d' => '7 hari',
            '30d' => '30 hari',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $dayCount = $this->filter === '30d' ? 29 : 6;
        $labels = [];
        $hadirSeries = [];
        $tidakHadirSeries = [];

        for ($i = 0; $i <= $dayCount; $i++) {
            $date = Carbon::today()->subDays($dayCount - $i);
            $labels[] = $date->translatedFormat('d M');

            $base = Attendance::query()
                ->whereHas('kbm', fn ($q) => $q->whereDate('date', $date->toDateString()));

            $hadirSeries[] = (clone $base)->where('status', 'HADIR')->count();
            $tidakHadirSeries[] = (clone $base)->whereIn('status', ['IZIN', 'SAKIT', 'ALPA'])->count();
        }

        $this->description = 'Hadir vs tidak hadir (I+S+A) per hari'.DashboardAcademicContext::statsSuffix();

        return [
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $hadirSeries,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.15)',
                    'fill' => true,
                ],
                [
                    'label' => 'Tidak hadir',
                    'data' => $tidakHadirSeries,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
