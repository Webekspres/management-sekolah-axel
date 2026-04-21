<?php

namespace App\Filament\Resources\Teachers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeachersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("name")->label("Nama"),
                TextColumn::make("nip")->label('NIP'),
                TextColumn::make("address")->label('Alamat'),
                TextColumn::make("employment_status")->label('Status Guru')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    1, true,
                    '1', 'true' => 'success',
                    default => 'danger',
                })
                ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Tidak Aktif'),
            ])
            ->filters([
                //
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
