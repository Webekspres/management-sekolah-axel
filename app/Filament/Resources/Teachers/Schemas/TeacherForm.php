<?php

namespace App\Filament\Resources\Teachers\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;

class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make("Guru")
                ->schema([
                    TextInput::make("name")->label("Nama Guru"),
                    TextInput::make("nip")->label("NIP"),
                    Grid::make(2)
                    ->schema([
                        TextInput::make("place_of_birth")->label("Tempat Lahir"),
                        DatePicker::make("date_of_birth")->label("Tanggal Lahir"),
                    ]),
                    TextInput::make("address")->label("Alamat"),
                    Checkbox::make("employment_status")->label("Guru Aktif")->default(true),
                ]),
            ]);
    }
}
