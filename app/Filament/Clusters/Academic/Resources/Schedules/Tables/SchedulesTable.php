<?php

namespace App\Filament\Clusters\Academic\Resources\Schedules\Tables;

use App\Filament\Guru\Resources\GradeInputs\GradeInputResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $query->with([
                    'schoolClass',
                    'subject',
                    'teacher.user',
                ]);

                return $query;
            })
            ->columns([
                TextColumn::make('schoolClass.name')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('day_of_week')
                    ->label('Hari')
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => 'Senin',
                        2 => 'Selasa',
                        3 => 'Rabu',
                        4 => 'Kamis',
                        5 => 'Jumat',
                        6 => 'Sabtu',
                        7 => 'Minggu',
                        default => 'Tidak diketahui',
                    })
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Mulai')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('Selesai')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('teacher.user.name')
                    ->label('Guru')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('schoolClass.name', 'asc')
            ->filters([
                SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->relationship('schoolClass', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('day_of_week')
                    ->label('Hari')
                    ->options([
                        1 => 'Senin',
                        2 => 'Selasa',
                        3 => 'Rabu',
                        4 => 'Kamis',
                        5 => 'Jumat',
                        6 => 'Sabtu',
                        7 => 'Minggu',
                    ])
                    ->multiple(),
                SelectFilter::make('teacher_id')
                    ->label('Guru')
                    ->relationship(
                        name: 'teacher',
                        titleAttribute: 'user_id',
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->join('users', 'users.id', '=', 'teachers.user_id')
                            ->select('teachers.*')
                            ->orderBy('users.name'),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? $record->nip)
                    ->searchable(['users.name', 'teachers.nip'])
                    ->preload()
                    ->multiple(),
            ])
            ->recordActions([
                Action::make('input_nilai')
                    ->label('Input Nilai')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('primary')
                    ->url(fn ($record): string => GradeInputResource::getUrl('index', ['schedule' => $record->id], panel: 'guru'))
                    ->visible(fn (): bool => auth()->user()?->role === 'guru')
                    ->openUrlInNewTab(false),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
