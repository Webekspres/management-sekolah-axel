<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Teachers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeachersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('user.gender')
                    ->label('Gender')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                        default => '-',
                    }),
                TextColumn::make('user.phone_number')
                    ->label('Nomor Telepon')
                    ->placeholder('-'),
                TextColumn::make('employment_status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'staff_tu' => 'Staff TU',
                        'non_staff_tu' => 'Guru Kelas',
                        'other' => 'Lainnya',
                        default => '-',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'staff_tu' => 'warning',
                        'non_staff_tu' => 'success',
                        'other' => 'gray',
                    })
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('employment_status')
                    ->label('Status')
                    ->options([
                        'staff_tu' => 'Staff TU',
                        'non_staff_tu' => 'Guru Kelas',
                        'other' => 'Lainnya',
                    ])
                    ->multiple(),
                Filter::make('gender')
                    ->label('Gender')
                    ->form([
                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->multiple(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['gender'] ?? null),
                            fn (Builder $query): Builder => $query->whereHas(
                                'user',
                                fn (Builder $userQuery): Builder => $userQuery->whereIn('gender', $data['gender']),
                            ),
                        );
                    }),
                Filter::make('is_active')
                    ->label('Status Akun')
                    ->form([
                        Select::make('is_active')
                            ->label('Status Akun')
                            ->options([
                                '1' => 'Aktif',
                                '0' => 'Nonaktif',
                            ])
                            ->multiple(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['is_active'] ?? null),
                            fn (Builder $query): Builder => $query->whereHas(
                                'user',
                                fn (Builder $userQuery): Builder => $userQuery->whereIn(
                                    'is_active',
                                    array_map(static fn (string $value): int => (int) $value, $data['is_active'])
                                ),
                            ),
                        );
                    }),
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
