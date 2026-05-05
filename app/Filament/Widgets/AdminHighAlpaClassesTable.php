<?php

namespace App\Filament\Widgets;

use App\Models\SchoolClass;
use App\Support\DashboardAcademicContext;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class AdminHighAlpaClassesTable extends TableWidget
{
    protected static ?int $sort = 5;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 6,
        'xl' => 6,
    ];

    public static function canView(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    public function table(Table $table): Table
    {
        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd = now()->endOfWeek()->toDateString();

        return $table
            ->heading('Alpa tinggi (minggu ini)')
            ->description('Kelas dengan jumlah ALPA terbanyak pada rentang minggu berjalan'.DashboardAcademicContext::statsSuffix())
            ->query(function () use ($weekEnd, $weekStart) {
                return SchoolClass::query()
                    ->select('classes.*')
                    ->selectRaw('(
                        SELECT COUNT(*) FROM attendances
                        INNER JOIN kbms ON attendances.kbm_id = kbms.id
                        INNER JOIN schedules ON kbms.schedule_id = schedules.id
                        WHERE schedules.class_id = classes.id
                        AND attendances.status = ?
                        AND kbms.date BETWEEN ? AND ?
                    ) AS alpa_week_count', ['ALPA', $weekStart, $weekEnd])
                    ->havingRaw('alpa_week_count > 0')
                    ->orderByDesc('alpa_week_count')
                    ->limit(40);
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Kelas'),
                TextColumn::make('alpa_week_count')
                    ->label('ALPA')
                    ->badge()
                    ->color('danger'),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
