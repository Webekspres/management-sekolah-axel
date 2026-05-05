<?php

namespace App\Filament\Kepsek\Widgets;

use App\Filament\Kepsek\Resources\LessonPlans\LessonPlanResource;
use App\Models\LessonPlan;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class KepsekPendingLessonPlansTable extends TableWidget
{
    protected static ?int $sort = 21;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 6,
    ];

    public static function canView(): bool
    {
        return Auth::user()?->role === 'kepala_sekolah';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('RPP menunggu persetujuan')
            ->description('5 teratas')
            ->query(fn (): Builder => LessonPlan::query()
                ->where('status', 'PENDING')
                ->with(['teacher.user', 'subjectForDisplay', 'schoolClass'])
                ->latest('implementation_date'))
            ->columns([
                TextColumn::make('implementation_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('subjectForDisplay.name')->label('Mapel')->limit(24),
                TextColumn::make('teacher.user.name')->label('Guru')->limit(24),
                TextColumn::make('topic')->label('Topik')->limit(40),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Buka')
                    ->url(fn (LessonPlan $record): string => LessonPlanResource::getUrl('edit', ['record' => $record], panel: 'kepsek')),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
