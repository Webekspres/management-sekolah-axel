<?php

namespace App\Filament\Student\Widgets;

use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentAttendanceTrendChart extends ChartWidget
{
    protected ?string $heading = 'Kehadiran 4 minggu terakhir';

    protected ?string $description = 'Persentase hadir pada KBM yang sudah disetujui';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '16rem';

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 20;

    public static function canView(): bool
    {
        return Auth::user()?->role === 'siswa_ortu';
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $student = Auth::user()?->student;

        $labels = [];
        $series = [];

        for ($w = 3; $w >= 0; $w--) {
            $start = now()->subWeeks($w)->copy()->startOfWeek()->toDateString();
            $end = now()->subWeeks($w)->copy()->endOfWeek()->toDateString();

            $labels[] = now()->subWeeks($w)->copy()->startOfWeek()->translatedFormat('d M');

            if ($student === null) {
                $series[] = 0;

                continue;
            }

            $base = Attendance::query()
                ->where('student_id', $student->id)
                ->whereHas('kbm', function (Builder $query) use ($end, $start): void {
                    $query
                        ->where('status', 'APPROVED')
                        ->whereBetween('date', [$start, $end]);
                });

            $total = (clone $base)->count();
            $hadir = (clone $base)->where('status', 'HADIR')->count();
            $series[] = $total > 0 ? (int) round(100 * $hadir / $total) : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => '% Hadir',
                    'data' => $series,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
