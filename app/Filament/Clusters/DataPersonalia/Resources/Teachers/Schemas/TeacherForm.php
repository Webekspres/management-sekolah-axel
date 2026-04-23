<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Teachers\Schemas;

use App\Models\City;
use App\Models\Province;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
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
                        Textarea::make('user.address_detail')
                            ->label('Detail Alamat')
                            ->placeholder('Nama perumahan, blok, gang, patokan, dll.')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),

                Section::make('Tempat & Tanggal Lahir')
                    ->description('Pilih lokasi tempat lahir dari data wilayah')
                    ->columns(2)
                    ->schema([
                        Select::make('user.birth_province_id')
                            ->label('Provinsi Lahir')
                            ->options(fn (): array => Province::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('user.place_of_birth', null);
                            }),
                        Select::make('user.place_of_birth')
                            ->label('Kota/Kabupaten Lahir')
                            ->options(function (Get $get): array {
                                $provinceId = $get('user.birth_province_id');

                                if (! $provinceId) {
                                    return [];
                                }

                                return City::query()
                                    ->where('province_id', $provinceId)
                                    ->orderBy('name')
                                    ->pluck('name', 'name')
                                    ->toArray();
                            })
                            ->searchable()
                            ->disabled(fn (Get $get): bool => ! $get('user.birth_province_id'))
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired(),
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
