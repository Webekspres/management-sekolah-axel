<?php

namespace App\Filament\Guru\Resources\PersonalityScores\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PersonalityScoresTable
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
                TextColumn::make('kedisiplinan')
                    ->label('Kedisiplinan')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success', 'B' => 'info', 'C' => 'warning', 'D' => 'danger', default => 'gray',
                    }),
                TextColumn::make('kerapihan')
                    ->label('Kerapihan')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success', 'B' => 'info', 'C' => 'warning', 'D' => 'danger', default => 'gray',
                    }),
                TextColumn::make('kerajinan')
                    ->label('Kerajinan')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success', 'B' => 'info', 'C' => 'warning', 'D' => 'danger', default => 'gray',
                    }),
                TextColumn::make('kesopanan')
                    ->label('Kesopanan')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success', 'B' => 'info', 'C' => 'warning', 'D' => 'danger', default => 'gray',
                    }),
            ])
            ->defaultSort('student.user.name', 'asc')
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
