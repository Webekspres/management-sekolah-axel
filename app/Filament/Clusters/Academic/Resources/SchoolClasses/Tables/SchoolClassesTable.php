<?php

namespace App\Filament\Clusters\Academic\Resources\SchoolClasses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchoolClassesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $query->with([
                    'level',
                    'academicYear',
                    'homeroomTeacher.user',
                ]);

                return $query;
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level.name')
                    ->label('Jenjang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('academicYear.name')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('homeroomTeacher.user.name')
                    ->label('Wali Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('homeroomTeacher.nip')
                    ->label('NIP')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('level_id')
                    ->label('Jenjang')
                    ->relationship('level', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('teacher_id')
                    ->label('Wali Kelas')
                    ->relationship(
                        name: 'homeroomTeacher',
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
