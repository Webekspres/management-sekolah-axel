<?php

namespace App\Filament\Kepsek\Widgets;

use App\Filament\Kepsek\Resources\Kbms\KbmResource;
use App\Models\Kbm;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class KepsekPendingKbmsTable extends TableWidget
{
    protected static ?int $sort = 10;

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
            ->heading('KBM menunggu persetujuan')
            ->description('5 teratas — klik Buka untuk review')
            ->query(fn (): Builder => Kbm::query()
                ->where('status', 'PENDING')
                ->with(['schedule.teacher.user', 'schedule.schoolClass', 'schedule.subjectForDisplay'])
                ->latest('date'))
            ->columns([
                TextColumn::make('date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('schedule.subjectForDisplay.name')->label('Mapel')->limit(24),
                TextColumn::make('schedule.teacher.user.name')->label('Guru')->limit(24),
                TextColumn::make('schedule.schoolClass.name')->label('Kelas'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Buka')
                    ->url(fn (Kbm $record): string => KbmResource::getUrl('edit', ['record' => $record], panel: 'kepsek')),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
