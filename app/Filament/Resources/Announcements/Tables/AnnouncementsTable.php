<?php

namespace App\Filament\Resources\Announcements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        $roleLabels = [
            'super_admin' => 'Admin',
            'kepala_sekolah' => 'Kepala sekolah',
            'guru' => 'Guru',
            'siswa_ortu' => 'Siswa dan Orang Tua',
        ];

        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('target_role')
                    ->formatStateUsing(function (mixed $state) use ($roleLabels): string {
                        $roles = is_array($state) ? $state : [$state];

                        return collect($roles)
                            ->filter()
                            ->map(fn (string $role): string => $roleLabels[$role] ?? $role)
                            ->implode(', ');
                    })
                    ->wrap(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
