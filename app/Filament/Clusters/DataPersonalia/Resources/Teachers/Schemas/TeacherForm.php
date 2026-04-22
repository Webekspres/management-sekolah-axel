<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Teachers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Akun')
                    ->description('Data login guru')
                    ->columns(2)
                    ->schema([
                        TextInput::make('user.name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('user.email')
                            ->label('Email')
                            ->email()
                            ->required(),
                        TextInput::make('user.password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->visibleOn('create'),
                        Radio::make('user.gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->default('L')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('user.phone_number')
                            ->label('Nomor Telepon')
                            ->tel(),
                        TextInput::make('user.place_of_birth')
                            ->label('Tempat Lahir'),
                        DatePicker::make('user.date_of_birth')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Data Kepegawaian')
                    ->description('Informasi jabatan dan status guru')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nip')
                            ->label('NIP'),
                        TextInput::make('employment_status')
                            ->label('Status Kepegawaian'),
                    ]),

            ]);
    }
}
