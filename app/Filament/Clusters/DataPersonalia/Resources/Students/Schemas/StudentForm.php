<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Students\Schemas;

use App\Models\SchoolClass;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Akun')
                    ->description('Data login siswa')
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
                                'male' => 'Laki-laki',
                                'female' => 'Perempuan',
                            ])
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

                Section::make('Data Akademik')
                    ->description('Informasi pendaftaran dan kelas')
                    ->columns(2)
                    ->schema([
                        Select::make('class_id')
                            ->label('Kelas')
                            ->options(
                                SchoolClass::query()
                                    ->get()
                                    ->mapWithKeys(fn (SchoolClass $class) => [
                                        $class->id => $class->name,
                                    ])
                            )
                            ->searchable()
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
                                'islam' => 'Islam',
                                'kristen' => 'Kristen',
                                'katolik' => 'Katolik',
                                'hindu' => 'Hindu',
                                'buddha' => 'Buddha',
                                'konghucu' => 'Konghucu',
                            ]),
                        TextInput::make('special_needs')
                            ->label('Kebutuhan Khusus'),
                        TextInput::make('student_phone')
                            ->label('Nomor Telepon Siswa')
                            ->tel(),
                    ]),

                Section::make('Alamat')
                    ->description('Tempat tinggal siswa saat ini')
                    ->columns(3)
                    ->schema([
                        TextInput::make('house_number')
                            ->label('Nomor Rumah'),
                        TextInput::make('rt')
                            ->label('RT'),
                        TextInput::make('rw')
                            ->label('RW'),
                        TextInput::make('village')
                            ->label('Kelurahan/Desa'),
                        TextInput::make('district')
                            ->label('Kecamatan'),
                        TextInput::make('city')
                            ->label('Kota/Kabupaten'),
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
