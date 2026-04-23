<?php

namespace App\Filament\Clusters\Academic\Resources\Subjects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level.name')
                    ->label('Jenjang')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('level_id')
                    ->label('Jenjang')
                    ->relationship('level', 'name')
                    ->searchable()
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
