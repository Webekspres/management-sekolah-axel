<?php

namespace App\Filament\Widgets;

use App\Models\Teacher;
use App\Support\DashboardAcademicContext;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AdminTeachersWithoutScheduleTable extends TableWidget
{
    protected static ?int $sort = 6;

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
            ->heading('Guru tanpa jadwal (TA aktif)')
            ->description('Belum punya slot di kelas tahun ajaran yang sedang aktif'.DashboardAcademicContext::statsSuffix())
            ->query(fn (): Builder => Teacher::query()
                ->with('user')
                ->whereDoesntHave('schedules.schoolClass.academicYear', fn ($q) => $q->where('is_active', true))
                ->orderBy('id'))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama')
                    ->placeholder('—'),
            ])
            ->defaultPaginationPageOption(6)
            ->paginated([6, 12]);
    }
}
