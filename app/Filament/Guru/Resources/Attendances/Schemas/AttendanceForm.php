<?php

namespace App\Filament\Guru\Resources\Attendances\Schemas;

use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Student;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Absensi')
                ->columns(2)
                ->schema([
                    Placeholder::make('kbm_info')
                        ->label('KBM')
                        ->content(function (?Attendance $record): string {
                            if ($record === null) {
                                return '—';
                            }

                            $kbm = Kbm::with(['schedule.schoolClass', 'schedule.subjectForDisplay'])
                                ->find($record->kbm_id);

                            return implode(' — ', array_filter([
                                $kbm?->date?->format('d M Y'),
                                $kbm?->schedule?->schoolClass?->name,
                                $kbm?->schedule?->subjectForDisplay?->name,
                            ])) ?: '—';
                        }),

                    Placeholder::make('student_info')
                        ->label('Siswa')
                        ->content(function (?Attendance $record): string {
                            if ($record === null) {
                                return '—';
                            }

                            $student = Student::withoutGlobalScopes()
                                ->with(['user', 'schoolClass'])
                                ->find($record->student_id);

                            return implode(' — ', array_filter([
                                $student?->user?->name,
                                $student?->schoolClass?->name,
                            ])) ?: '—';
                        }),

                    Select::make('status')
                        ->label('Status Kehadiran')
                        ->options([
                            'HADIR' => 'Hadir',
                            'SAKIT' => 'Sakit',
                            'IZIN' => 'Izin',
                            'ALPA' => 'Alpa',
                        ])
                        ->required()
                        ->in(['HADIR', 'SAKIT', 'IZIN', 'ALPA']),
                ]),
        ]);
    }
}
