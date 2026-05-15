<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Staff\Schemas;

use App\Models\City;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Data Akun')
                    ->description('Informasi login dan identitas dasar')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        Select::make('role')
                            ->label('Role')
                            ->options([
                                'super_admin' => 'Super Admin',
                                'kepala_sekolah' => 'Kepala Sekolah',
                            ])
                            ->required()
                            ->default('kepala_sekolah'),
                        Radio::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->default('L')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('phone_number')
                            ->label('Nomor Telepon')
                            ->tel(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),

                Section::make('Tempat & Tanggal Lahir')
                    ->description('Pilih lokasi tempat lahir dari data wilayah')
                    ->columns(2)
                    ->schema([
                        Select::make('birth_province_id')
                            ->label('Provinsi Lahir')
                            ->options(fn (): array => Province::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('place_of_birth', null);
                            }),
                        Select::make('place_of_birth')
                            ->label('Kota/Kabupaten Lahir')
                            ->options(function (Get $get): array {
                                $provinceId = $get('birth_province_id');

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
                            ->live()
                            ->disabled(fn (Get $get): bool => ! $get('birth_province_id'))
                            ->required(fn (Get $get): bool => filled($get('birth_province_id'))),
                        DatePicker::make('date_of_birth')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Alamat Domisili')
                    ->description('Pilih lokasi alamat tinggal dari data wilayah')
                    ->columns(3)
                    ->schema([
                        Select::make('address_province_id')
                            ->label('Provinsi')
                            ->options(fn (): array => Province::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('address_city_id', null);
                                $set('address_sub_district_id', null);
                                $set('address_village_id', null);
                            }),
                        Select::make('address_city_id')
                            ->label('Kota/Kabupaten')
                            ->options(function (Get $get): array {
                                $provinceId = $get('address_province_id');

                                if (! $provinceId) {
                                    return [];
                                }

                                return City::query()
                                    ->where('province_id', $provinceId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->disabled(fn (Get $get): bool => ! $get('address_province_id'))
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('address_sub_district_id', null);
                                $set('address_village_id', null);
                            }),
                        Select::make('address_sub_district_id')
                            ->label('Kecamatan')
                            ->options(function (Get $get): array {
                                $cityId = $get('address_city_id');

                                if (! $cityId) {
                                    return [];
                                }

                                return SubDistrict::query()
                                    ->where('city_id', $cityId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->disabled(fn (Get $get): bool => ! $get('address_city_id'))
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('address_village_id', null);
                            }),
                        Select::make('address_village_id')
                            ->label('Desa/Kelurahan')
                            ->options(function (Get $get): array {
                                $subDistrictId = $get('address_sub_district_id');

                                if (! $subDistrictId) {
                                    return [];
                                }

                                return Village::query()
                                    ->where('sub_district_id', $subDistrictId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->disabled(fn (Get $get): bool => ! $get('address_sub_district_id')),
                        TextInput::make('address_street')
                            ->label('Jalan/Gang/Nomor')
                            ->placeholder('Jl. Mawar No. 10, RT 02/RW 05')
                            ->columnSpan(2),
                        TextInput::make('address_postal_code')
                            ->label('Kode Pos')
                            ->maxLength(5),
                        Textarea::make('address_detail')
                            ->label('Detail Alamat Tambahan')
                            ->placeholder('Nama perumahan, blok, gang, patokan, dll.')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),

                Section::make('Data Kepegawaian')
                    ->description('Nomor Induk Pegawai')
                    ->schema([
                        TextInput::make('nip')
                            ->label('NIP'),
                    ]),

            ]);
    }
}
