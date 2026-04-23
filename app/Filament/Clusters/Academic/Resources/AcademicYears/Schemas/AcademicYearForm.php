<?php

namespace App\Filament\Clusters\Academic\Resources\AcademicYears\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AcademicYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Tahun Ajaran')
                    ->placeholder('2025/2026')
                    ->required()
                    ->maxLength(255),
                Select::make('semester')
                    ->label('Semester')
                    ->options([
                        'Ganjil' => 'Ganjil',
                        'Genap' => 'Genap',
                    ])
                    ->required(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->required(),
            ]);
    }
}
