<?php

namespace App\Filament\Guru\Resources\LearningAchievements\Schemas;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class LearningAchievementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identifikasi')
                ->columns(3)
                ->schema([
                    Select::make('student_id')
                        ->label('Siswa')
                        ->options(function (): array {
                            /** @var User $user */
                            $user = auth()->user();

                            if (! $user?->teacher) {
                                return [];
                            }

                            $classIds = Schedule::where('teacher_id', $user->teacher->id)
                                ->pluck('class_id');

                            return Student::whereIn('class_id', $classIds)
                                ->with('user')
                                ->get()
                                ->mapWithKeys(fn (Student $s) => [$s->id => $s->user?->name ?? $s->id])
                                ->all();
                        })
                        ->searchable()
                        ->required()
                        ->live(),

                    Select::make('subject_id')
                        ->label('Mata Pelajaran')
                        ->options(function (): array {
                            /** @var User $user */
                            $user = auth()->user();

                            if (! $user?->teacher) {
                                return [];
                            }

                            return Schedule::where('teacher_id', $user->teacher->id)
                                ->with('subject')
                                ->get()
                                ->mapWithKeys(fn (Schedule $s) => [$s->subject_id => $s->subject?->name ?? $s->subject_id])
                                ->unique()
                                ->all();
                        })
                        ->searchable()
                        ->required()
                        ->live(),

                    Select::make('academic_year_id')
                        ->label('Tahun Akademik')
                        ->options(fn () => AcademicYear::orderByDesc('name')->pluck('name', 'id')->all())
                        ->default(fn () => AcademicYear::where('is_active', true)->value('id'))
                        ->required()
                        ->live(),
                ]),

            Section::make('Referensi Nilai')
                ->columns(3)
                ->schema([
                    Placeholder::make('ph_avg')
                        ->label('Rata-rata PH')
                        ->content(function (Get $get): string {
                            $studentId = $get('student_id');
                            $subjectId = $get('subject_id');
                            $academicYearId = $get('academic_year_id');

                            if (! $studentId || ! $subjectId || ! $academicYearId) {
                                return '—';
                            }

                            $avg = Grade::where([
                                'student_id' => $studentId,
                                'subject_id' => $subjectId,
                                'academic_year_id' => $academicYearId,
                            ])->whereIn('grade_type', Grade::PH_TYPES)->avg('score');

                            return $avg !== null ? number_format((float) $avg, 2) : '—';
                        }),

                    Placeholder::make('ats_score')
                        ->label('Nilai ATS')
                        ->content(function (Get $get): string {
                            $studentId = $get('student_id');
                            $subjectId = $get('subject_id');
                            $academicYearId = $get('academic_year_id');

                            if (! $studentId || ! $subjectId || ! $academicYearId) {
                                return '—';
                            }

                            $score = Grade::where([
                                'student_id' => $studentId,
                                'subject_id' => $subjectId,
                                'academic_year_id' => $academicYearId,
                                'grade_type' => 'ATS',
                            ])->value('score');

                            return $score !== null ? number_format((float) $score, 2) : '—';
                        }),

                    Placeholder::make('sas_score')
                        ->label('Nilai SAS')
                        ->content(function (Get $get): string {
                            $studentId = $get('student_id');
                            $subjectId = $get('subject_id');
                            $academicYearId = $get('academic_year_id');

                            if (! $studentId || ! $subjectId || ! $academicYearId) {
                                return '—';
                            }

                            $score = Grade::where([
                                'student_id' => $studentId,
                                'subject_id' => $subjectId,
                                'academic_year_id' => $academicYearId,
                                'grade_type' => 'SAS',
                            ])->value('score');

                            return $score !== null ? number_format((float) $score, 2) : '—';
                        }),
                ]),

            Section::make('Capaian Pembelajaran')
                ->schema([
                    Textarea::make('topic_coverage')
                        ->label('Pemaparan Materi')
                        ->rows(4)
                        ->nullable()
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('Keterangan')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
