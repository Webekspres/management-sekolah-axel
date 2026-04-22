<?php

namespace App\Filament\Clusters\Academic\Resources\SchoolClasses\Schemas;

use App\Models\Teacher;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                TextInput::make('name')
                    ->label('Nama Kelas')
                    ->required()
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
                    ->required()
                    ->live(),
                Select::make('level_id')
                    ->label('Jenjang')
                    ->relationship('level', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('teacher_id')
                    ->label('Wali Kelas')
                    ->relationship(
                        name: 'homeroomTeacher',
                        titleAttribute: 'nip',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->with('user'),
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (Teacher $record): string => $record->nip
                            ? "{$record->user?->name} ({$record->nip})"
                            : ($record->user?->name ?? '-')
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }
}
