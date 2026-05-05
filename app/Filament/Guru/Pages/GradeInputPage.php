<?php

namespace App\Filament\Guru\Pages;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use App\Services\RaporService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class GradeInputPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static UnitEnum|string|null $navigationGroup = 'Akademik';

    protected static ?string $navigationLabel = 'Input Nilai';

    protected static ?string $title = 'Input Nilai';

    protected static ?string $slug = 'input-nilai/{schedule}';

    protected string $view = 'filament.guru.pages.grade-input-page';

    /** @var array<string, array<string, string>> */
    public array $grades = [];

    /** The schedule ID from the route parameter. */
    public string $schedule = '';

    /** @var array<int, Student> */
    public array $students = [];

    /** Whether an active academic year exists (used in view). */
    public bool $hasActiveAcademicYear = false;

    /** The resolved Schedule model (not serialized by Livewire). */
    protected ?Schedule $scheduleModel = null;

    /** The active academic year (not serialized by Livewire). */
    protected ?AcademicYear $activeAcademicYear = null;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->role === 'guru';
    }

    public function mount(string $schedule): void
    {
        $this->schedule = $schedule;

        /** @var User $user */
        $user = auth()->user();

        // Authorization: guru can only access their own schedules
        $scheduleModel = Schedule::with(['schoolClass.students.user', 'subject'])
            ->where('id', $schedule)
            ->where('teacher_id', $user->teacher?->id)
            ->first();

        if (! $scheduleModel) {
            abort(403, 'Anda tidak memiliki akses ke jadwal ini.');
        }

        $this->scheduleModel = $scheduleModel;

        $this->activeAcademicYear = AcademicYear::where('is_active', true)->first();

        if (! $this->activeAcademicYear) {
            $this->hasActiveAcademicYear = false;
            $this->students = [];
            $this->grades = [];

            return;
        }

        $this->hasActiveAcademicYear = true;
        $this->students = $scheduleModel->schoolClass->students->all();

        $this->loadExistingGrades();
    }

    /**
     * Resolve the Schedule model, loading it from DB if not already cached.
     */
    protected function resolveSchedule(): ?Schedule
    {
        if ($this->scheduleModel) {
            return $this->scheduleModel;
        }

        if (! $this->schedule) {
            return null;
        }

        /** @var User $user */
        $user = auth()->user();

        $this->scheduleModel = Schedule::with(['schoolClass.students.user', 'subject'])
            ->where('id', $this->schedule)
            ->where('teacher_id', $user->teacher?->id)
            ->first();

        return $this->scheduleModel;
    }

    /**
     * Resolve the active academic year, loading it from DB if not already cached.
     */
    protected function resolveAcademicYear(): ?AcademicYear
    {
        if ($this->activeAcademicYear) {
            return $this->activeAcademicYear;
        }

        $this->activeAcademicYear = AcademicYear::where('is_active', true)->first();

        return $this->activeAcademicYear;
    }

    private function loadExistingGrades(): void
    {
        $academicYear = $this->resolveAcademicYear();
        $scheduleModel = $this->resolveSchedule();

        if (! $academicYear || ! $scheduleModel) {
            return;
        }

        $studentIds = collect($this->students)->pluck('id')->all();

        $existingGrades = Grade::where('subject_id', $scheduleModel->subject_id)
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('student_id', $studentIds)
            ->get();

        // Initialize grades array with empty strings
        foreach ($this->students as $student) {
            $this->grades[$student->id] = [];
            foreach (Grade::GRADE_TYPES as $type) {
                $this->grades[$student->id][$type] = '';
            }
        }

        // Fill in existing grades
        foreach ($existingGrades as $grade) {
            if (isset($this->grades[$grade->student_id])) {
                $this->grades[$grade->student_id][$grade->grade_type] = $grade->score !== null
                    ? (string) $grade->score
                    : '';
            }
        }
    }

    public function saveGrades(): void
    {
        // Validate all inputs
        $validationRules = [];
        foreach ($this->students as $student) {
            foreach (array_merge(Grade::PH_TYPES, Grade::TUGAS_TYPES, ['ATS', 'SAS']) as $type) {
                $key = "grades.{$student->id}.{$type}";
                $validationRules[$key] = ['nullable', 'numeric', 'between:0,100'];
            }
        }

        $this->validate($validationRules);

        $academicYear = $this->resolveAcademicYear();
        $scheduleModel = $this->resolveSchedule();

        if (! $academicYear || ! $scheduleModel) {
            Notification::make()
                ->title('Gagal menyimpan nilai')
                ->body('Tahun akademik aktif tidak ditemukan.')
                ->danger()
                ->send();

            return;
        }

        // Build grade data array
        $gradeData = [];
        foreach ($this->students as $student) {
            foreach (array_merge(Grade::PH_TYPES, Grade::TUGAS_TYPES, ['ATS', 'SAS']) as $type) {
                $value = $this->grades[$student->id][$type] ?? '';
                if ($value !== '' && $value !== null) {
                    $gradeData[] = [
                        'student_id' => $student->id,
                        'grade_type' => $type,
                        'score' => (float) $value,
                    ];
                }
            }
        }

        try {
            /** @var RaporService $raporService */
            $raporService = app(RaporService::class);
            $raporService->saveGrades($gradeData, $this->schedule, $academicYear->id);

            // Reload grades to show updated RAPOR values
            $this->loadExistingGrades();

            Notification::make()
                ->title('Nilai berhasil disimpan')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Log::error('GradeInputPage: gagal menyimpan nilai', [
                'schedule_id' => $this->schedule,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Gagal menyimpan nilai')
                ->body('Terjadi kesalahan saat menyimpan. Semua perubahan dibatalkan.')
                ->danger()
                ->send();
        }
    }

    public static function getNavigationItems(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        $scheduleModel = $this->resolveSchedule();

        if ($scheduleModel) {
            $subject = $scheduleModel->subject?->name ?? 'Mata Pelajaran';
            $class = $scheduleModel->schoolClass?->name ?? 'Kelas';

            return "Input Nilai — {$subject} ({$class})";
        }

        return 'Input Nilai';
    }

    /**
     * @return array<string>
     */
    public function getInputGradeTypes(): array
    {
        return array_merge(Grade::PH_TYPES, Grade::TUGAS_TYPES, ['ATS', 'SAS']);
    }
}
