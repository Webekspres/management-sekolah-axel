<?php

namespace App\Filament\Guru\Widgets;

use App\Filament\Guru\Resources\Attendances\AttendanceResource;
use App\Filament\Guru\Resources\Kbms\KbmResource;
use App\Models\Kbm;
use App\Models\Schedule;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GuruTodayChecklistTable extends TableWidget
{
    protected static ?int $sort = 5;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'guru';
    }

    public function table(Table $table): Table
    {
        $teacherId = Auth::user()?->teacher?->id;

        return $table
            ->heading('Checklist mengajar hari ini')
            ->description('Status KBM per jadwal — tautan cepat input')
            ->query(function () use ($teacherId): Builder {
                if ($teacherId === null) {
                    return Schedule::query()->whereRaw('1 = 0');
                }

                return Schedule::query()
                    ->where('teacher_id', $teacherId)
                    ->where('day_of_week', now()->dayOfWeekIso)
                    ->with(['schoolClass', 'subjectForDisplay', 'kbms' => fn ($q) => $q->whereDate('date', today())])
                    ->orderBy('start_time');
            })
            ->columns([
                TextColumn::make('start_time')->label('Mulai')->time('H:i'),
                TextColumn::make('end_time')->label('Selesai')->time('H:i'),
                TextColumn::make('schoolClass.name')->label('Kelas'),
                TextColumn::make('subjectForDisplay.name')->label('Mapel')->limit(28),
                TextColumn::make('kbm_state')
                    ->label('KBM')
                    ->badge()
                    ->state(function (Schedule $record): string {
                        $kbm = $record->kbms->first();

                        if ($kbm === null) {
                            return 'Belum ada';
                        }

                        return $kbm->status;
                    })
                    ->color(function (Schedule $record): string {
                        $kbm = $record->kbms->first();
                        if ($kbm === null) {
                            return 'danger';
                        }

                        return match ($kbm->status) {
                            'APPROVED' => 'success',
                            'PENDING' => 'warning',
                            'DRAFT', 'REVISED' => 'gray',
                            default => 'gray',
                        };
                    }),
            ])
            ->recordActions([
                Action::make('kbm')
                    ->label(fn (Schedule $record): string => $record->kbms->first() ? 'Lanjut KBM' : 'Isi KBM')
                    ->url(function (Schedule $record): string {
                        $kbm = $record->kbms->first();
                        if ($kbm instanceof Kbm) {
                            return KbmResource::getUrl('edit', ['record' => $kbm], panel: 'guru');
                        }

                        return KbmResource::getUrl('create', panel: 'guru').'?schedule_id='.$record->id.'&date='.today()->toDateString();
                    }),
                Action::make('absensi')
                    ->label('Absensi')
                    ->url(fn (): string => AttendanceResource::getUrl(panel: 'guru'))
                    ->color('gray'),
            ])
            ->defaultPaginationPageOption(6)
            ->paginated([6, 12]);
    }
}
