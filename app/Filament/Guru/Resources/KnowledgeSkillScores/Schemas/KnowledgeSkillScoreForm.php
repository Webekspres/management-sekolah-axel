<?php

namespace App\Filament\Guru\Resources\KnowledgeSkillScores\Schemas;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectKkm;
use App\Models\User;
use App\Services\RaporService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class KnowledgeSkillScoreForm
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
                        ->required(),

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
                        ->required(),
                ]),

            Section::make('Nilai Pengetahuan')
                ->columns(2)
                ->schema([
                    Placeholder::make('kkm_info')
                        ->label('KKM Mata Pelajaran')
                        ->content(function (Get $get): string {
                            $subjectId = $get('subject_id');
                            if (! $subjectId) {
                                return '70 (default)';
                            }

                            $subject = Subject::with('level')->find($subjectId);
                            $levelId = $subject?->level_id;

                            if (! $levelId) {
                                return '70 (default)';
                            }

                            $kkm = SubjectKkm::getKkm($subjectId, $levelId);

                            return number_format($kkm, 2);
                        }),

                    TextInput::make('knowledge_score')
                        ->label('Nilai Pengetahuan (0–100)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            if ($state !== null && $state !== '') {
                                $predicate = app(RaporService::class)->assignPredicate((float) $state);
                                $set('knowledge_predicate', $predicate);
                            }
                        }),

                    TextInput::make('knowledge_predicate')
                        ->label('Predikat Pengetahuan')
                        ->readOnly()
                        ->placeholder('Otomatis'),

                    Textarea::make('knowledge_description')
                        ->label('Deskripsi Pengetahuan')
                        ->rows(2)
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('Nilai Keterampilan')
                ->columns(2)
                ->schema([
                    TextInput::make('skill_score')
                        ->label('Nilai Keterampilan (0–100)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            if ($state !== null && $state !== '') {
                                $predicate = app(RaporService::class)->assignPredicate((float) $state);
                                $set('skill_predicate', $predicate);
                            }
                        }),

                    TextInput::make('skill_predicate')
                        ->label('Predikat Keterampilan')
                        ->readOnly()
                        ->placeholder('Otomatis'),

                    Textarea::make('skill_description')
                        ->label('Deskripsi Keterampilan')
                        ->rows(2)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
