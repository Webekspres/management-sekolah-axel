<?php

namespace App\Filament\Clusters\Academic\Resources\Subjects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Mata Pelajaran')
                    ->required()
                    ->maxLength(255),
                Select::make('level_id')
                    ->label('Jenjang')
                    ->relationship('level', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }
}
