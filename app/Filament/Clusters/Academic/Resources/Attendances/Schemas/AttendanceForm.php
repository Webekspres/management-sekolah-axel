<?php

namespace App\Filament\Clusters\Academic\Resources\Attendances\Schemas;

use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\SchoolClass;
use App\Models\Student;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AttendanceForm
{
    /**
     * Form untuk halaman Create — cascading: Kelas → Tanggal → Mata Pelajaran → Siswa → Status
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Absensi')
                    ->columns(2)
                    ->schema([
                        Select::make('class_id')
                            ->label('Kelas')
                            ->options(fn (): array => SchoolClass::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()
                            )
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set): void {
                                $set('kbm_date', null);
                                $set('kbm_id', null);
                                $set('student_id', null);
                            })
                            ->dehydrated(false),

                        Select::make('kbm_date')
                            ->label('Tanggal KBM')
                            ->options(function (Get $get): array {
                                $classId = $get('class_id');

                                if (! $classId) {
                                    return [];
                                }

                                return Kbm::query()
                                    ->whereHas('schedule', fn ($q) => $q->where('class_id', $classId))
                                    ->orderByDesc('date')
                                    ->get()
                                    ->mapWithKeys(fn (Kbm $kbm): array => [
                                        $kbm->date->toDateString() => $kbm->date->format('d M Y'),
                                    ])
                                    ->unique()
                                    ->all();
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->disabled(fn (Get $get): bool => ! $get('class_id'))
                            ->afterStateUpdated(function (callable $set): void {
                                $set('kbm_id', null);
                                $set('student_id', null);
                            })
                            ->dehydrated(false),

                        Select::make('kbm_id')
                            ->label('Mata Pelajaran')
                            ->options(function (Get $get): array {
                                $classId = $get('class_id');
                                $date = $get('kbm_date');

                                if (! $classId || ! $date) {
                                    return [];
                                }

                                return Kbm::query()
                                    ->with(['schedule.subjectForDisplay'])
                                    ->whereHas('schedule', fn ($q) => $q->where('class_id', $classId))
                                    ->whereDate('date', $date)
                                    ->get()
                                    ->mapWithKeys(fn (Kbm $kbm): array => [
                                        $kbm->id => $kbm->schedule?->subjectForDisplay?->name ?? $kbm->id,
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->disabled(fn (Get $get): bool => ! $get('kbm_date'))
                            ->afterStateUpdated(fn (callable $set) => $set('student_id', null)),

                        Select::make('student_id')
                            ->label('Siswa')
                            ->options(function (Get $get): array {
                                $classId = $get('class_id');

                                if (! $classId) {
                                    return [];
                                }

                                return Student::withoutGlobalScopes()
                                    ->with('user')
                                    ->where('class_id', $classId)
                                    ->get()
                                    ->mapWithKeys(fn (Student $student): array => [
                                        $student->id => $student->user?->name ?? $student->id,
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->required()
                            ->disabled(fn (Get $get): bool => ! $get('kbm_id'))
                            ->rules([
                                fn (Get $get, ?Attendance $record): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get, $record): void {
                                    $kbmId = $get('kbm_id');

                                    if (! $kbmId || ! $value) {
                                        return;
                                    }

                                    $exists = Attendance::query()
                                        ->where('kbm_id', $kbmId)
                                        ->where('student_id', $value)
                                        ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                        ->exists();

                                    if ($exists) {
                                        $fail('Siswa ini sudah memiliki record absensi untuk KBM tersebut.');
                                    }
                                },
                            ]),

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

    /**
     * Form untuk halaman Edit — hanya status yang bisa diubah, info lainnya readonly.
     */
    public static function configureForEdit(Schema $schema): Schema
    {
        return $schema
            ->components([
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
