<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Students\Tables;

use App\Models\AcademicYear;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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
                TextColumn::make('schoolClass.name')
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
                    ->preload()
                    ->multiple(),
                Filter::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->form([
                        Select::make('academic_year_id')
                            ->label('Tahun Ajaran')
                            ->options(
                                AcademicYear::query()
                                    ->orderByDesc('name')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->multiple(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['academic_year_id'] ?? null),
                            fn (Builder $query): Builder => $query->whereHas(
                                'schoolClass',
                                fn (Builder $classQuery): Builder => $classQuery->whereIn('academic_year_id', $data['academic_year_id']),
                            ),
                        );
                    }),
                Filter::make('gender')
                    ->label('Jenis Kelamin')
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
                Filter::make('admission_date')
                    ->label('Tanggal Masuk')
                    ->form([
                        DatePicker::make('admission_from')
                            ->label('Dari Tanggal')
                            ->native(false),
                        DatePicker::make('admission_until')
                            ->label('Sampai Tanggal')
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
