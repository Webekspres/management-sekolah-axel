<?php

namespace App\Filament\Clusters\Academic\Resources\Attendances\Tables;

use App\Models\Attendance;
use App\Models\SchoolClass;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
                TextColumn::make('kbm.date')
                    ->label('Tanggal KBM')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('kbm.schedule.schoolClass.name')
                    ->label('Kelas')
                    ->state(fn (Attendance $record): ?string => $record->kbm?->schedule?->schoolClass?->name)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'kbm.schedule.schoolClass',
                        fn (Builder $q): Builder => $q->where('name', 'like', "%{$search}%"),
                    )),
                TextColumn::make('kbm.schedule.subjectForDisplay.name')
                    ->label('Mata Pelajaran')
                    ->state(fn (Attendance $record): ?string => $record->kbm?->schedule?->subjectForDisplay?->name)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'kbm.schedule.subjectForDisplay',
                        fn (Builder $q): Builder => $q->where('name', 'like', "%{$search}%"),
                    )),
                TextColumn::make('student_name')
                    ->label('Nama Siswa')
                    ->state(fn (Attendance $record): ?string => $record->student?->user?->name)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'student.user',
                        fn (Builder $q): Builder => $q->where('name', 'like', "%{$search}%"),
                    )),
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
                TextColumn::make('teacher_name')
                    ->label('Nama Guru')
                    ->state(fn (Attendance $record): ?string => $record->kbm?->schedule?->teacher?->user?->name)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'kbm.schedule.teacher.user',
                        fn (Builder $q): Builder => $q->where('name', 'like', "%{$search}%"),
                    )),
            ])
            ->defaultSort('kbm.date', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
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
                SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->options(
                        SchoolClass::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                    )
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $query): Builder => $query->whereHas(
                                'kbm.schedule',
                                fn (Builder $scheduleQuery): Builder => $scheduleQuery->where('class_id', $data['value']),
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
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
