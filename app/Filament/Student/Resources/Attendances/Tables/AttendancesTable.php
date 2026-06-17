<?php

namespace App\Filament\Student\Resources\Attendances\Tables;

use App\Models\Attendance;
use App\Models\Kbm;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kbm_date')
                    ->label('Tanggal KBM')
                    ->state(fn (Attendance $record): ?string => $record->kbm?->date?->format('d M Y'))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy(
                        Kbm::select('date')
                            ->whereColumn('kbms.id', 'attendances.kbm_id')
                            ->limit(1),
                        $direction,
                    )),
                TextColumn::make('subject_name')
                    ->label('Mata Pelajaran')
                    ->state(fn (Attendance $record): ?string => $record->kbm?->schedule?->subjectForDisplay?->name),
                TextColumn::make('class_name')
                    ->label('Kelas')
                    ->state(fn (Attendance $record): ?string => $record->kbm?->schedule?->schoolClass?->name),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'HADIR' => 'Hadir',
                        'SAKIT' => 'Sakit',
                        'IZIN' => 'Izin',
                        'ALPA' => 'Alpa',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'HADIR' => 'success',
                        'SAKIT' => 'warning',
                        'IZIN' => 'info',
                        'ALPA' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->orderBy(
                Kbm::select('date')
                    ->whereColumn('kbms.id', 'attendances.kbm_id')
                    ->limit(1),
                'desc',
            ))
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->filters([
                Filter::make('date_range')
                    ->label('Rentang Tanggal KBM')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('Dari Tanggal')
                            ->native(false),
                        DatePicker::make('date_until')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereHas(
                                    'kbm',
                                    fn (Builder $kbmQuery): Builder => $kbmQuery->whereDate('date', '>=', $date),
                                ),
                            )
                            ->when(
                                $data['date_until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereHas(
                                    'kbm',
                                    fn (Builder $kbmQuery): Builder => $kbmQuery->whereDate('date', '<=', $date),
                                ),
                            );
                    }),
                SelectFilter::make('status')
                    ->label('Status Kehadiran')
                    ->options([
                        'HADIR' => 'Hadir',
                        'SAKIT' => 'Sakit',
                        'IZIN' => 'Izin',
                        'ALPA' => 'Alpa',
                    ]),
            ])
            ->emptyStateHeading('Belum ada data absensi')
            ->emptyStateDescription('Data absensi Anda akan muncul di sini setelah guru menginput kehadiran.')
            ->recordActions([]);
    }
}
