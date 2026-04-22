<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Teachers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
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
                Filter::make('employment_status')
                    ->label('Status')
                    ->schema([
                        Select::make('employment_status')
                            ->label('Status Kepegawaian')
                            ->options([
                                'staff_tu' => 'Staff TU',
                                'non_staff_tu' => 'Guru Kelas',
                                'other' => 'Lainnya',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['employment_status'] ?? null,
                            fn (Builder $query, string $status): Builder => $query->where('employment_status', $status),
                        );
                    }),
                Filter::make('gender')
                    ->label('Gender')
                    ->schema([
                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['gender'] ?? null,
                            fn (Builder $query, string $gender): Builder => $query->whereHas(
                                'user',
                                fn (Builder $userQuery): Builder => $userQuery->where('gender', $gender),
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
