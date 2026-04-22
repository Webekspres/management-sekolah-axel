<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Students\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nipd')
                    ->label('NIPD')
                    ->searchable(),
                TextColumn::make('class.name')
                    ->label('Kelas')
                    ->sortable(),
                TextColumn::make('student_phone')
                    ->label('Telepon Siswa'),
                TextColumn::make('father_name')
                    ->label('Nama Ayah'),
                TextColumn::make('father_phone')
                    ->label('Telepon Ayah'),
                TextColumn::make('mother_name')
                    ->label('Nama Ibu'),
                TextColumn::make('mother_phone')
                    ->label('Telepon Ibu'),
                TextColumn::make('admission_date')
                    ->label('Tanggal Masuk')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->relationship('schoolClass', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('admission_date')
                    ->label('Tanggal Masuk')
                    ->schema([
                        DatePicker::make('admission_from')
                            ->label('Tanggal Masuk')
                            ->native(false),
                        DatePicker::make('admission_until')
                            ->label('Tanggal Lulus/Keluar')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['admission_from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('admission_date', '>=', $date),
                            )
                            ->when(
                                $data['admission_until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('admission_date', '<=', $date),
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
