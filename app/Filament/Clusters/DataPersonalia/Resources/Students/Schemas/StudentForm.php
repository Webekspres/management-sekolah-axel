<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Students\Schemas;

use App\Models\City;
use App\Models\Province;
use App\Models\SchoolClass;
use App\Models\SubDistrict;
use App\Models\Village;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Data Akun')
                    ->description('Informasi login dan identitas dasar')
                    ->columns(2)
                    ->schema([
                        TextInput::make('user.name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        TextInput::make('user.email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true),
                        TextInput::make('user.password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        Radio::make('user.gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('user.phone_number')
                            ->label('Nomor Telepon')
                            ->tel(),
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
                            ->live()
                            ->disabled(fn (Get $get): bool => ! $get('user.birth_province_id'))
                            ->required(fn (Get $get): bool => filled($get('user.birth_province_id'))),
                        DatePicker::make('user.date_of_birth')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Data Akademik')
                    ->description('Informasi pendaftaran dan kelas')
                    ->columns(2)
                    ->schema([
                        Select::make('class_id')
                            ->label('Kelas')
                            ->options(fn (): array => SchoolClass::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('nipd')
                            ->label('NIPD')
                            ->required(),
                        TextInput::make('school_code')
                            ->label('Kode Sekolah'),
                        DatePicker::make('admission_date')
                            ->label('Tanggal Masuk')
                            ->native(false),
                        TextInput::make('origin_school')
                            ->label('Asal Sekolah'),
                        TextInput::make('diploma_number')
                            ->label('Nomor Ijazah'),
                        DatePicker::make('diploma_date')
                            ->label('Tanggal Ijazah')
                            ->native(false),
                        TextInput::make('custom_spp')
                            ->label('SPP Khusus')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp'),
                    ]),

                Section::make('Data Diri')
                    ->description('Identitas kependudukan siswa')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nik')
                            ->label('NIK')
                            ->required()
                            ->maxLength(16),
                        TextInput::make('nisn')
                            ->label('NISN')
                            ->required(),
                        TextInput::make('kk_number')
                            ->label('Nomor KK'),
                        TextInput::make('birth_cert_number')
                            ->label('Nomor Akta Kelahiran'),
                        Select::make('religion')
                            ->label('Agama')
                            ->options([
                                'Islam' => 'Islam',
                                'Kristen' => 'Kristen',
                                'Katolik' => 'Katolik',
                                'Hindu' => 'Hindu',
                                'Buddha' => 'Buddha',
                                'Konghucu' => 'Konghucu',
                            ]),
                        TextInput::make('special_needs')
                            ->label('Kebutuhan Khusus'),
                        TextInput::make('student_phone')
                            ->label('Nomor Telepon Siswa')
                            ->tel(),
                    ]),

                Section::make('Alamat Domisili')
                    ->description('Pilih lokasi alamat tinggal dari data wilayah')
                    ->columns(2)
                    ->schema([
                        Select::make('address_province_id')
                            ->label('Provinsi')
                            ->options(fn (): array => Province::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('city', null);
                                $set('address_city_id', null);
                                $set('district', null);
                                $set('address_sub_district_id', null);
                                $set('village', null);
                            })
                            ->dehydrated(false),
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
                            ->afterStateUpdated(function (Set $set, $state): void {
                                $set('city', $state ? City::find($state)?->name : null);
                                $set('district', null);
                                $set('address_sub_district_id', null);
                                $set('village', null);
                            })
                            ->dehydrated(false),
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
                            ->afterStateUpdated(function (Set $set, $state): void {
                                $set('district', $state ? SubDistrict::find($state)?->name : null);
                                $set('village', null);
                            })
                            ->dehydrated(false),
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
                            ->disabled(fn (Get $get): bool => ! $get('address_sub_district_id'))
                            ->afterStateUpdated(function (Set $set, $state): void {
                                $set('village', $state ? Village::find($state)?->name : null);
                            })
                            ->dehydrated(false),

                        TextInput::make('city')
                            ->label('Kota/Kabupaten')
                            ->hidden(),
                        TextInput::make('district')
                            ->label('Kecamatan')
                            ->hidden(),
                        TextInput::make('village')
                            ->label('Kelurahan/Desa')
                            ->hidden(),

                        TextInput::make('house_number')
                            ->label('Nomor Rumah'),
                        TextInput::make('rt')
                            ->label('RT'),
                        TextInput::make('rw')
                            ->label('RW'),
                        Textarea::make('user.address_detail')
                            ->label('Detail Alamat')
                            ->placeholder('Nama perumahan, blok, gang, patokan, dll.')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),

                Section::make('Data Orang Tua')
                    ->description('Informasi kontak wali siswa')
                    ->columns(2)
                    ->schema([
                        TextInput::make('father_name')
                            ->label('Nama Ayah'),
                        TextInput::make('father_phone')
                            ->label('Nomor Telepon Ayah')
                            ->tel(),
                        TextInput::make('mother_name')
                            ->label('Nama Ibu'),
                        TextInput::make('mother_phone')
                            ->label('Nomor Telepon Ibu')
                            ->tel(),
                    ]),

            ]);
    }
}
