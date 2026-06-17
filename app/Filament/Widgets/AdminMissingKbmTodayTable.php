<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use App\Support\DashboardAcademicContext;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AdminMissingKbmTodayTable extends TableWidget
{
    protected static ?int $sort = 4;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Tanpa KBM hari ini')
            ->description('Jadwal terjadwal belum ada laporan KBM untuk tanggal hari ini'.DashboardAcademicContext::statsSuffix())
            ->query(fn (): Builder => Schedule::query()
                ->where('day_of_week', now()->dayOfWeekIso)
                ->whereDoesntHave('kbms', fn ($q) => $q->whereDate('date', today()))
                ->with(['schoolClass', 'subjectForDisplay', 'teacher.user'])
                ->orderBy('start_time'))
            ->columns([
                TextColumn::make('schoolClass.name')
                    ->label('Kelas')
                    ->placeholder('—'),
                TextColumn::make('subjectForDisplay.name')
                    ->label('Mapel')
                    ->limit(22),
                TextColumn::make('teacher.user.name')
                    ->label('Guru')
                    ->limit(20),
                TextColumn::make('start_time')
                    ->label('Mulai')
                    ->time('H:i'),
                TextColumn::make('end_time')
                    ->label('Selesai')
                    ->time('H:i'),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
