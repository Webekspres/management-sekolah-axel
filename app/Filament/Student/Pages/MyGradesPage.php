<?php

namespace App\Filament\Student\Pages;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MyGradesPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static UnitEnum|string|null $navigationGroup = 'Akademik';

    protected static ?string $navigationLabel = 'Nilai Saya';

    protected static ?string $title = 'Nilai Saya';

    protected static ?string $slug = 'nilai-saya';

    protected string $view = 'filament.student.pages.my-grades-page';

    /** @var array<string, array<string, mixed>> Grades grouped by subject */
    public array $gradesBySubject = [];

    public bool $hasStudentProfile = false;

    public ?string $activeAcademicYearName = null;

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

        $academicYear = AcademicYear::where('is_active', true)->first();
        $this->activeAcademicYearName = $academicYear?->name;

        if (! $academicYear) {
            return;
        }

        $grades = Grade::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->with('subject')
            ->get();

        $this->gradesBySubject = $grades
            ->groupBy('subject_id')
            ->map(function ($subjectGrades) {
                $subject = $subjectGrades->first()->subject;
                $gradeMap = $subjectGrades->keyBy('grade_type');

                return [
                    'subject_name' => $subject?->name ?? '—',
                    'grades' => $gradeMap->map(fn ($g) => (string) $g->score)->all(),
                ];
            })
            ->values()
            ->all();
    }
}
