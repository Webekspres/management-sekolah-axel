<?php

namespace App\Filament\Clusters\Academic\Resources\SchoolClasses\Schemas;

use App\Models\Teacher;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class SchoolClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kelas')
                    ->description('Data kelas dan penugasan wali kelas')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Kelas')
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired()
                            ->maxLength(255)
                            ->unique(
                                table: 'classes',
                                column: 'name',
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where(
                                    'academic_year_id',
                                    $get('academic_year_id')
                                ),
                            ),
                        Select::make('academic_year_id')
                            ->label('Tahun Ajaran')
                            ->relationship('academicYear', 'name')
                            ->searchable()
                            ->preload()
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired()
                            ->live(),
                        Select::make('level_id')
                            ->label('Jenjang')
                            ->relationship('level', 'name')
                            ->searchable()
                            ->preload()
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired(),
                        Select::make('teacher_id')
                            ->label('Wali Kelas')
                            ->relationship(
                                name: 'homeroomTeacher',
                                titleAttribute: 'user_id',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->join('users', 'users.id', '=', 'teachers.user_id')
                                    ->select('teachers.*'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (Teacher $record): string => $record->nip
                                    ? "{$record->user?->name} ({$record->nip})"
                                    : ($record->user?->name ?? '-')
                            )
                            ->searchable(['users.name', 'teachers.nip'])
                            ->preload()
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired(),
                        TextInput::make('kkm')
                            ->label('KKM')
                            ->helperText('Kosongkan jika menggunakan KKM default per mata pelajaran. Default: 70')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->nullable()
                            ->placeholder('Contoh: 75.00'),
                    ]),
            ]);
    }
}
