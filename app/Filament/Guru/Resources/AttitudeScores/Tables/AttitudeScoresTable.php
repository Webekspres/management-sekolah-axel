<?php

namespace App\Filament\Guru\Resources\AttitudeScores\Tables;

use App\Models\AttitudeScore;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttitudeScoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['student.user', 'academicYear']))
            ->columns([
                TextColumn::make('student.user.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('academicYear.name')
                    ->label('Tahun Akademik')
                    ->sortable(),
                TextColumn::make('aspect')
                    ->label('Aspek')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('score')
                    ->label('Nilai')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('average_score')
                    ->label('Rata-rata Sikap')
                    ->state(function (AttitudeScore $record): string {
                        $avg = AttitudeScore::where('student_id', $record->student_id)
                            ->where('academic_year_id', $record->academic_year_id)
                            ->avg('score');

                        return $avg !== null ? number_format((float) $avg, 2) : '—';
                    })
                    ->badge()
                    ->color('success'),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('student.user.name', 'asc')
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('aspect')
                    ->label('Aspek')
                    ->options([
                        'Spiritual' => 'Spiritual',
                        'Sosial' => 'Sosial',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
