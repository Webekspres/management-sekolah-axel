<?php

namespace App\Filament\Clusters\Academic\Resources\AcademicYears\Schemas;

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
                    ->required(),
                TextInput::make('semester')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
