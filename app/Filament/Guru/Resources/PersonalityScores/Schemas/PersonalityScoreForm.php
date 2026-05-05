<?php

namespace App\Filament\Guru\Resources\PersonalityScores\Schemas;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PersonalityScoreForm
{
    /** @return array<string, string> */
    private static function gradeOptions(): array
    {
        return [
            'A' => 'A — Sangat Baik',
            'B' => 'B — Baik',
            'C' => 'C — Cukup',
            'D' => 'D — Kurang',
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identifikasi')
                ->columns(2)
                ->schema([
                    Select::make('student_id')
                        ->label('Siswa')
                        ->options(function (): array {
                            /** @var User $user */
                            $user = auth()->user();

                            if (! $user?->teacher) {
                                return [];
                            }

                            $classIds = SchoolClass::where('teacher_id', $user->teacher->id)
                                ->pluck('id');

                            return Student::whereIn('class_id', $classIds)
                                ->with('user')
                                ->get()
                                ->mapWithKeys(fn (Student $s) => [$s->id => $s->user?->name ?? $s->id])
                                ->all();
                        })
                        ->searchable()
                        ->required(),

                    Select::make('academic_year_id')
                        ->label('Tahun Akademik')
                        ->options(fn () => AcademicYear::orderByDesc('name')->pluck('name', 'id')->all())
                        ->default(fn () => AcademicYear::where('is_active', true)->value('id'))
                        ->required(),
                ]),

            Section::make('Penilaian Kepribadian')
                ->columns(2)
                ->schema([
                    Select::make('kedisiplinan')
                        ->label('Kedisiplinan')
                        ->options(self::gradeOptions())
                        ->in(['A', 'B', 'C', 'D'])
                        ->required(),

                    Select::make('kerapihan')
                        ->label('Kerapihan')
                        ->options(self::gradeOptions())
                        ->in(['A', 'B', 'C', 'D'])
                        ->required(),

                    Select::make('kerajinan')
                        ->label('Kerajinan')
                        ->options(self::gradeOptions())
                        ->in(['A', 'B', 'C', 'D'])
                        ->required(),

                    Select::make('kesopanan')
                        ->label('Kesopanan')
                        ->options(self::gradeOptions())
                        ->in(['A', 'B', 'C', 'D'])
                        ->required(),
                ]),
        ]);
    }
}
