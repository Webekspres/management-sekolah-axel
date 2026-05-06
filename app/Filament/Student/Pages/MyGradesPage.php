<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Widgets\GradeStatsWidget;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use UnitEnum;

class MyGradesPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static UnitEnum|string|null $navigationGroup = 'Akademik';

    protected static ?string $navigationLabel = 'Nilai Saya';

    protected static ?string $title = 'Nilai Saya';

    protected static ?string $slug = 'nilai-saya';

    protected string $view = 'filament.student.pages.my-grades-page';

    public bool $hasStudentProfile = false;

    public ?string $activeAcademicYearName = null;

    public ?string $levelId = null;

    public function getHeaderWidgets(): array
    {
        return [GradeStatsWidget::class];
    }

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;

        if (! $student) {
            $this->hasStudentProfile = false;

            return;
        }

        $this->hasStudentProfile = true;
        $this->levelId = $student->schoolClass?->level_id;

        $academicYear = AcademicYear::where('is_active', true)->first();
        $this->activeAcademicYearName = $academicYear?->name;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (array $filters): Collection {
                $records = $this->getGradeRecords();

                $selectedSubject = $filters['subject_name']['value'] ?? null;

                if (filled($selectedSubject)) {
                    $records = $records->filter(
                        fn (array $record): bool => $record['subject_name'] === $selectedSubject
                    );
                }

                return $records;
            })
            ->columns([
                TextColumn::make('subject_name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('PH1')->label('PH 1')->placeholder('—')->alignCenter(),
                TextColumn::make('PH2')->label('PH 2')->placeholder('—')->alignCenter(),
                TextColumn::make('PH3')->label('PH 3')->placeholder('—')->alignCenter(),
                TextColumn::make('PH4')->label('PH 4')->placeholder('—')->alignCenter(),
                TextColumn::make('TUGAS1')->label('Tugas 1')->placeholder('—')->alignCenter(),
                TextColumn::make('TUGAS2')->label('Tugas 2')->placeholder('—')->alignCenter(),
                TextColumn::make('TUGAS3')->label('Tugas 3')->placeholder('—')->alignCenter(),
                TextColumn::make('TUGAS4')->label('Tugas 4')->placeholder('—')->alignCenter(),
                TextColumn::make('ATS')->label('ATS')->placeholder('—')->alignCenter(),
                TextColumn::make('SAS')->label('SAS')->placeholder('—')->alignCenter(),
                TextColumn::make('RAPOR')
                    ->label('Rapor')
                    ->placeholder('—')
                    ->alignCenter()
                    ->weight('bold')
                    ->color('primary'),
            ])
            ->filters([
                SelectFilter::make('subject_name')
                    ->label('Mata Pelajaran')
                    ->options(fn (): array => $this->getSubjectOptions()),
            ])
            ->emptyStateHeading('Belum ada nilai')
            ->emptyStateDescription('Nilai untuk tahun akademik aktif belum tersedia.')
            ->emptyStateIcon(Heroicon::OutlinedClipboardDocumentList)
            ->paginated(false);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function getGradeRecords(): Collection
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;

        if (! $student) {
            return collect();
        }

        $academicYear = AcademicYear::where('is_active', true)->first();

        if (! $academicYear) {
            return collect();
        }

        $grades = Grade::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->with('subject')
            ->get();

        return $grades
            ->groupBy('subject_id')
            ->map(function (Collection $subjectGrades): array {
                $subject = $subjectGrades->first()->subject;
                $gradeMap = $subjectGrades->keyBy('grade_type');

                $row = [
                    'subject_name' => $subject?->name ?? '—',
                    'subject_id' => $subject?->id,
                ];

                foreach (Grade::GRADE_TYPES as $type) {
                    $row[$type] = isset($gradeMap[$type])
                        ? number_format((float) $gradeMap[$type]->score, 2)
                        : null;
                }

                return $row;
            })
            ->values();
    }

    /** @return array<string, string> */
    private function getSubjectOptions(): array
    {
        return $this->getGradeRecords()
            ->pluck('subject_name', 'subject_name')
            ->all();
    }
}
