<?php

namespace App\Filament\Clusters\Academic\Resources\SubjectKkms\Schemas;

use App\Models\Level;
use App\Models\Subject;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubjectKkmForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('subject_id')
                ->label('Mata Pelajaran')
                ->options(fn () => Subject::orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->required(),

            Select::make('level_id')
                ->label('Jenjang / Level')
                ->options(fn () => Level::orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->required(),

            TextInput::make('kkm')
                ->label('Nilai KKM (0–100)')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->step(0.01)
                ->default(70.00)
                ->required(),
        ]);
    }
}
