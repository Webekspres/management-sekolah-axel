<?php

namespace App\Filament\Kepsek\Widgets;

use App\Models\Schedule;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class KepsekTodayScheduleTable extends TableWidget
{
    protected static ?int $sort = 12;

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
        $now = now()->format('H:i:s');

        return $table
            ->heading('Jadwal mengajar hari ini')
            ->description('Slot berdasarkan hari ini — status waktu relatif')
            ->query(fn (): Builder => Schedule::query()
                ->where('day_of_week', now()->dayOfWeekIso)
                ->with(['schoolClass', 'subjectForDisplay', 'teacher.user', 'kbms' => fn ($q) => $q->whereDate('date', today())])
                ->orderBy('start_time'))
            ->columns([
                TextColumn::make('time_slot')
                    ->label('Slot')
                    ->state(function (Schedule $record): string {
                        return Str::substr((string) $record->start_time, 0, 5).'–'.Str::substr((string) $record->end_time, 0, 5);
                    }),
                TextColumn::make('schoolClass.name')->label('Kelas'),
                TextColumn::make('subjectForDisplay.name')->label('Mapel')->limit(28),
                TextColumn::make('teacher.user.name')->label('Guru')->limit(24),
                TextColumn::make('kbm_status')
                    ->label('KBM hari ini')
                    ->badge()
                    ->state(function (Schedule $record): string {
                        $kbm = $record->kbms->first();

                        return $kbm ? $kbm->status : 'BELUM ADA';
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
                TextColumn::make('slot_status')
                    ->label('Sesi')
                    ->badge()
                    ->color(function (Schedule $record) use ($now): string {
                        if ($now < $record->start_time) {
                            return 'warning';
                        }
                        if ($now > $record->end_time) {
                            return 'gray';
                        }

                        return 'success';
                    })
                    ->state(function (Schedule $record) use ($now): string {
                        if ($now < $record->start_time) {
                            return 'Akan datang';
                        }
                        if ($now > $record->end_time) {
                            return 'Selesai';
                        }

                        return 'Berlangsung';
                    }),
            ])
            ->defaultPaginationPageOption(6)
            ->paginated([6, 12]);
    }
}
