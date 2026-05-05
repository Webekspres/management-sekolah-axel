<?php

namespace App\Filament\Guru\Widgets;

use App\Models\Schedule;
use App\Support\DashboardAcademicContext;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class GuruWeeklyTeachingLoadChart extends ChartWidget
{
    protected ?string $heading = 'Beban jadwal 7 hari ke depan';

    protected ?string $description = 'Jumlah slot mengajar berdasarkan pola mingguan';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '16rem';

    protected static ?int $sort = 11;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 6,
    ];

    public static function canView(): bool
    {
        return Auth::user()?->role === 'guru';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $teacherId = Auth::user()?->teacher?->id;
        $labels = [];
        $counts = [];

        for ($i = 0; $i < 7; $i++) {
            $day = now()->addDays($i);
            $labels[] = $day->translatedFormat('D d M');

            if ($teacherId === null) {
                $counts[] = 0;

                continue;
            }

            $counts[] = Schedule::query()
                ->where('teacher_id', $teacherId)
                ->where('day_of_week', $day->dayOfWeekIso)
                ->count();
        }

        $this->description = 'Jumlah slot mengajar berdasarkan pola mingguan'.DashboardAcademicContext::statsSuffix();

        return [
            'datasets' => [
                [
                    'label' => 'Slot jadwal',
                    'data' => $counts,
                    'backgroundColor' => '#0ea5e9',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
