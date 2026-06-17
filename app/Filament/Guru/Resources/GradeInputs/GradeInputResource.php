<?php

namespace App\Filament\Guru\Resources\GradeInputs;

use App\Filament\Guru\Resources\GradeInputs\Pages\ListGradeInputs;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\Student;
use App\Services\RaporService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class GradeInputResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static UnitEnum|string|null $navigationGroup = 'Akademik';

    protected static ?string $label = 'Input Nilai';

    protected static ?string $pluralLabel = 'Input Nilai';

    protected static ?string $slug = 'input-nilai';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Grade::class) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return static::buildTable($table, null, null);
    }

    /**
     * Build the grade input table for a specific schedule and academic year.
     */
    public static function buildTable(Table $table, ?Schedule $schedule, ?AcademicYear $academicYear): Table
    {
        $subjectId = $schedule?->subject_id;
        $academicYearId = $academicYear?->id;

        $makeGradeColumn = function (string $gradeType, string $label, string $color = 'gray') use ($subjectId, $academicYearId): TextInputColumn {
            return TextInputColumn::make("grade_{$gradeType}")
                ->label($label)
                ->type('number')
                ->extraAttributes(['min' => '0', 'max' => '100', 'step' => '0.01'])
                ->rules(['nullable', 'numeric', 'between:0,100'])
                ->placeholder('—')
                ->state(function (Student $record) use ($gradeType, $subjectId, $academicYearId): ?string {
                    if (! $subjectId || ! $academicYearId) {
                        return null;
                    }

                    $score = Grade::where([
                        'student_id' => $record->id,
                        'subject_id' => $subjectId,
                        'academic_year_id' => $academicYearId,
                        'grade_type' => $gradeType,
                    ])->value('score');

                    return $score !== null ? (string) $score : null;
                })
                ->updateStateUsing(function (Student $record, ?string $state) use ($gradeType, $subjectId, $academicYearId): void {
                    if (! $subjectId || ! $academicYearId) {
                        return;
                    }

                    try {
                        $raporService = app(RaporService::class);

                        // Always ensure rapor record exists first
                        $raporService->ensureRaporExists($record->id, $academicYearId);

                        if ($state !== null && $state !== '') {
                            $raporService->upsertGrade(
                                $record->id,
                                $subjectId,
                                $academicYearId,
                                $gradeType,
                                (float) $state,
                            );
                        } else {
                            // Delete the grade if cleared
                            Grade::where([
                                'student_id' => $record->id,
                                'subject_id' => $subjectId,
                                'academic_year_id' => $academicYearId,
                                'grade_type' => $gradeType,
                            ])->delete();
                        }

                        // Recalculate RAPOR score
                        $raporService->recalculateRaporScore($record->id, $subjectId, $academicYearId);
                    } catch (\Throwable $e) {
                        Log::error('GradeInputResource: gagal menyimpan nilai', [
                            'student_id' => $record->id,
                            'grade_type' => $gradeType,
                            'error' => $e->getMessage(),
                        ]);
                    }
                });
        };

        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),

                // ── Penilaian Harian ──────────────────────────────────
                $makeGradeColumn('PH1', 'PH 1'),
                $makeGradeColumn('PH2', 'PH 2'),
                $makeGradeColumn('PH3', 'PH 3'),
                $makeGradeColumn('PH4', 'PH 4'),

                // ── Tugas / PR ────────────────────────────────────────
                $makeGradeColumn('TUGAS1', 'T 1'),
                $makeGradeColumn('TUGAS2', 'T 2'),
                $makeGradeColumn('TUGAS3', 'T 3'),
                $makeGradeColumn('TUGAS4', 'T 4'),

                // ── Asesmen ───────────────────────────────────────────
                $makeGradeColumn('ATS', 'ATS'),
                $makeGradeColumn('SAS', 'SAS'),

                // ── Nilai Rapor (read-only) ───────────────────────────
                TextColumn::make('rapor_score')
                    ->label('Nilai Rapor')
                    ->state(function (Student $record) use ($subjectId, $academicYearId): ?string {
                        if (! $subjectId || ! $academicYearId) {
                            return null;
                        }

                        $score = Grade::where([
                            'student_id' => $record->id,
                            'subject_id' => $subjectId,
                            'academic_year_id' => $academicYearId,
                            'grade_type' => 'RAPOR',
                        ])->value('score');

                        return $score !== null ? number_format((float) $score, 2) : '—';
                    })
                    ->badge()
                    ->color(function (Student $record) use ($subjectId, $academicYearId): string {
                        if (! $subjectId || ! $academicYearId) {
                            return 'gray';
                        }

                        $score = (float) Grade::where([
                            'student_id' => $record->id,
                            'subject_id' => $subjectId,
                            'academic_year_id' => $academicYearId,
                            'grade_type' => 'RAPOR',
                        ])->value('score');

                        return match (true) {
                            $score >= 86 => 'success',
                            $score >= 73 => 'info',
                            $score >= 60 => 'warning',
                            $score > 0 => 'danger',
                            default => 'gray',
                        };
                    })
                    ->alignCenter(),
            ])
            ->defaultSort('user.name', 'asc')
            ->striped()
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGradeInputs::route('/{schedule}'),
        ];
    }
}
