<?php

namespace App\Filament\Guru\Resources\GradeInputs\Pages;

use App\Filament\Guru\Resources\GradeInputs\GradeInputResource;
use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListGradeInputs extends ListRecords
{
    protected static string $resource = GradeInputResource::class;

    /** Stored as public so Livewire serializes it across requests. */
    public string $scheduleId = '';

    protected ?Schedule $scheduleModel = null;

    protected ?AcademicYear $activeAcademicYear = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();

        // Read schedule ID from route parameter on initial load
        $scheduleId = request()->route('schedule') ?? '';
        $this->scheduleId = (string) $scheduleId;

        $this->resolveSchedule();

        parent::mount();
    }

    protected function resolveSchedule(): void
    {
        if ($this->scheduleModel) {
            return;
        }

        if (! $this->scheduleId) {
            abort(403);
        }

        /** @var User $user */
        $user = auth()->user();

        $this->scheduleModel = Schedule::with(['schoolClass.students.user', 'subject'])
            ->where('id', $this->scheduleId)
            ->where('teacher_id', $user->teacher?->id)
            ->first();

        if (! $this->scheduleModel) {
            abort(403, 'Anda tidak memiliki akses ke jadwal ini.');
        }

        $this->activeAcademicYear = AcademicYear::where('is_active', true)->first();
    }

    protected function getSchedule(): ?Schedule
    {
        $this->resolveSchedule();

        return $this->scheduleModel;
    }

    protected function getAcademicYear(): ?AcademicYear
    {
        if (! $this->activeAcademicYear) {
            $this->activeAcademicYear = AcademicYear::where('is_active', true)->first();
        }

        return $this->activeAcademicYear;
    }

    public function getTitle(): string|Htmlable
    {
        $schedule = $this->getSchedule();

        if ($schedule) {
            $subject = $schedule->subject?->name ?? 'Mata Pelajaran';
            $class = $schedule->schoolClass?->name ?? 'Kelas';

            return "Input Nilai — {$subject} ({$class})";
        }

        return 'Input Nilai';
    }

    public function getBreadcrumbs(): array
    {
        // Prevent Filament from generating breadcrumb URLs without the schedule parameter
        return [];
    }

    public function getSubheading(): ?string
    {
        $schedule = $this->getSchedule();
        $academicYear = $this->getAcademicYear();

        if (! $schedule || ! $academicYear) {
            return null;
        }

        $subject = $schedule->subject?->name ?? '—';
        $class = $schedule->schoolClass?->name ?? '—';
        $year = $academicYear->name;
        $count = $schedule->schoolClass?->students?->count() ?? 0;

        return "Mata Pelajaran: {$subject} · Kelas: {$class} · Tahun Akademik: {$year} · {$count} siswa";
    }

    public function table(Table $table): Table
    {
        return GradeInputResource::buildTable(
            $table,
            $this->getSchedule(),
            $this->getAcademicYear(),
        );
    }

    protected function getTableQuery(): Builder
    {
        $schedule = $this->getSchedule();

        if (! $schedule) {
            return Student::query()->whereRaw('1 = 0');
        }

        $classId = $schedule->class_id;

        return Student::query()
            ->where('class_id', $classId)
            ->with('user')
            ->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'students.user_id')
                    ->limit(1)
            );
    }
}
