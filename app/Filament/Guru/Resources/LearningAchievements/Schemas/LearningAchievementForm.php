<?php

namespace App\Filament\Guru\Resources\LearningAchievements\Schemas;

use App\Helpers\PredicateCalculator;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

                    Placeholder::make('suggested_ph_predicate')
                        ->label('Predikat PH')
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

                            return PredicateCalculator::calculate($avg !== null ? (float) $avg : null);
                        }),

                    Placeholder::make('suggested_ats_predicate')
                        ->label('Predikat ATS')
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

                            return PredicateCalculator::calculate($score !== null ? (float) $score : null);
                        }),

                    Placeholder::make('suggested_sas_predicate')
                        ->label('Predikat SAS')
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

                            return PredicateCalculator::calculate($score !== null ? (float) $score : null);
                        }),
                ]),

            Section::make('Hasil Pembelajaran')
                ->columns(3)
                ->schema([
                    Select::make('daily_assessment_predicate')
                        ->label('Penilaian Harian')
                        ->options([
                            'Kurang' => 'Kurang',
                            'Cukup' => 'Cukup',
                            'Baik' => 'Baik',
                            'Sangat Baik' => 'Sangat Baik',
                        ])
                        ->nullable(),

                    Select::make('midterm_assessment_predicate')
                        ->label('Asesmen Tengah Semester')
                        ->options([
                            'Kurang' => 'Kurang',
                            'Cukup' => 'Cukup',
                            'Baik' => 'Baik',
                            'Sangat Baik' => 'Sangat Baik',
                        ])
                        ->nullable(),

                    Select::make('final_assessment_predicate')
                        ->label('Sumatif Akhir Semester')
                        ->options([
                            'Kurang' => 'Kurang',
                            'Cukup' => 'Cukup',
                            'Baik' => 'Baik',
                            'Sangat Baik' => 'Sangat Baik',
                        ])
                        ->nullable(),
                ]),

            Section::make('Capaian Pembelajaran')
                ->schema([
                    Radio::make('material_coverage_status')
                        ->label('Status Pemaparan Materi')
                        ->options([
                            'Terpenuhi' => 'Terpenuhi',
                            'Tidak Terpenuhi' => 'Tidak Terpenuhi',
                        ])
                        ->inline()
                        ->nullable()
                        ->columnSpanFull(),

                    Textarea::make('topic_coverage')
                        ->label('Pemaparan Materi')
                        ->rows(4)
                        ->nullable()
                        ->columnSpanFull(),

                    TextInput::make('achievement_status')
                        ->label('Keterangan Capaian')
                        ->placeholder('Contoh: Terlampaui, Berkembang, dll')
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
