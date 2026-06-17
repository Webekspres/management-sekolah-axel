<?php

namespace App\Filament\Student\Resources\Announcements\Tables;

use App\Models\Announcement;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('read_status')
                    ->label('Status Baca')
                    ->state(fn (Announcement $record): string => $record->isRead() ? 'Sudah Dibaca' : 'Belum Dibaca')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sudah Dibaca' => 'success',
                        default => 'warning',
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
