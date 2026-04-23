<?php

namespace App\Filament\Clusters\Academic\Resources\Schedules\Schemas;

use App\Models\Schedule;
use App\Models\Teacher;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Get;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('class_id')
                            ->label('Kelas')
                            ->relationship('schoolClass', 'name')
                            ->searchable()
                            ->preload()
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired(),
                        Select::make('subject_id')
                            ->label('Mata Pelajaran')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload()
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired(),
                        Select::make('teacher_id')
                            ->label('Guru Pengajar')
                            ->relationship(
                                name: 'teacher',
                                titleAttribute: 'nip',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->with('user'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (Teacher $record): string => $record->nip
                                    ? "{$record->user?->name} ({$record->nip})"
                                    : ($record->user?->name ?? '-')
                            )
                            ->searchable()
                            ->preload()
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired(),
                        Select::make('day_of_week')
                            ->label('Hari')
                            ->options([
                                1 => 'Senin',
                                2 => 'Selasa',
                                3 => 'Rabu',
                                4 => 'Kamis',
                                5 => 'Jumat',
                                6 => 'Sabtu',
                                7 => 'Minggu',
                            ])
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired(),
                        TimePicker::make('start_time')
                            ->label('Waktu Mulai')
                            ->seconds(false)
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired(),
                        TimePicker::make('end_time')
                            ->label('Waktu Selesai')
                            ->seconds(false)
                            ->after('start_time')
                            ->required(false)
                            ->markAsRequired()
                            ->rules([
                                'required',
                                fn (Get $get, ?Schedule $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $classId = $get('class_id');
                                    $teacherId = $get('teacher_id');
                                    $dayOfWeek = $get('day_of_week');
                                    $startTime = $get('start_time');
                                    $endTime = $value;

                                    if (! $classId || ! $teacherId || ! $dayOfWeek || ! $startTime || ! $endTime) {
                                        return;
                                    }

                                    // Cek bentrok kelas
                                    $classConflict = Schedule::where('class_id', $classId)
                                        ->where('day_of_week', $dayOfWeek)
                                        ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                        ->where(function ($query) use ($startTime, $endTime) {
                                            $query->whereBetween('start_time', [$startTime, $endTime])
                                                ->orWhereBetween('end_time', [$startTime, $endTime])
                                                ->orWhere(function ($q) use ($startTime, $endTime) {
                                                    $q->where('start_time', '<=', $startTime)
                                                        ->where('end_time', '>=', $endTime);
                                                });
                                        })
                                        ->exists();

                                    if ($classConflict) {
                                        $fail('Jadwal bentrok untuk kelas ini pada rentang waktu yang dipilih.');

                                        return;
                                    }

                                    // Cek bentrok guru
                                    $teacherConflict = Schedule::where('teacher_id', $teacherId)
                                        ->where('day_of_week', $dayOfWeek)
                                        ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                        ->where(function ($query) use ($startTime, $endTime) {
                                            $query->whereBetween('start_time', [$startTime, $endTime])
                                                ->orWhereBetween('end_time', [$startTime, $endTime])
                                                ->orWhere(function ($q) use ($startTime, $endTime) {
                                                    $q->where('start_time', '<=', $startTime)
                                                        ->where('end_time', '>=', $endTime);
                                                });
                                        })
                                        ->exists();

                                    if ($teacherConflict) {
                                        $fail('Guru ini sudah memiliki jadwal mengajar di kelas lain pada waktu tersebut.');
                                    }
                                },
                            ]),
                    ]),
            ]);
    }
}
