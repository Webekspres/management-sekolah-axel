<?php

namespace App\Filament\Guru\Resources\Kbms\Pages;

use App\Filament\Guru\Resources\Kbms\KbmResource;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InputKbmAttendance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = KbmResource::class;

    protected string $view = 'filament.guru.resources.kbms.pages.input-kbm-attendance';

    public Kbm $kbm;

    /**
     * @var array<string, string>
     */
    protected array $attendanceStatuses = [];

    public function mount(string $record): void
    {
        $this->kbm = KbmResource::getEloquentQuery()
            ->with(['schedule.schoolClass', 'schedule.subjectForDisplay'])
            ->findOrFail($record);

        $this->loadAttendanceStatuses();
    }

    public function getTitle(): string
    {
        $className = $this->kbm->schedule?->schoolClass?->name ?? '-';
        $subjectName = $this->kbm->schedule?->subjectForDisplay?->name ?? '-';

        return "Input Absensi — {$className} / {$subjectName}";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Student::withoutGlobalScopes()
                    ->with('user')
                    ->where('class_id', $this->kbm->schedule->class_id)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                SelectColumn::make('attendance_status')
                    ->label('Status Kehadiran')
                    ->options($this->attendanceOptions())
                    ->selectablePlaceholder(false)
                    ->state(fn (Student $record): string => $this->attendanceStatuses[$record->id] ?? 'HADIR')
                    ->updateStateUsing(function (Student $record, string $state): void {
                        $this->setStudentStatus($record->id, $state);
                    }),
            ])
            ->headerActions([
                Action::make('setAllHadir')
                    ->label('Set Semua Hadir')
                    ->color('success')
                    ->action(fn (): Notification => $this->setAllStatus('HADIR')),
                Action::make('setAllSakit')
                    ->label('Set Semua Sakit')
                    ->color('warning')
                    ->action(fn (): Notification => $this->setAllStatus('SAKIT')),
                Action::make('setAllIzin')
                    ->label('Set Semua Izin')
                    ->color('info')
                    ->action(fn (): Notification => $this->setAllStatus('IZIN')),
                Action::make('setAllAlpa')
                    ->label('Set Semua Alpa')
                    ->color('danger')
                    ->action(fn (): Notification => $this->setAllStatus('ALPA')),
            ])
            ->emptyStateHeading('Tidak ada siswa di kelas ini')
            ->emptyStateDescription('Tambahkan siswa ke kelas ini terlebih dahulu, lalu input absensi kembali.');
    }

    public function setStudentStatus(string $studentId, string $status): void
    {
        if (! array_key_exists($status, $this->attendanceOptions())) {
            return;
        }

        DB::transaction(function () use ($studentId, $status): void {
            Attendance::upsert(
                [[
                    'id' => (string) Str::ulid(),
                    'kbm_id' => $this->kbm->id,
                    'student_id' => $studentId,
                    'status' => $status,
                ]],
                uniqueBy: ['kbm_id', 'student_id'],
                update: ['status']
            );
        });

        $this->attendanceStatuses[$studentId] = $status;
    }

    public function setAllStatus(string $status): Notification
    {
        if (! array_key_exists($status, $this->attendanceOptions())) {
            return Notification::make()
                ->title('Status tidak valid')
                ->danger()
                ->send();
        }

        $students = Student::withoutGlobalScopes()
            ->where('class_id', $this->kbm->schedule->class_id)
            ->get(['id']);

        $payload = $students->map(fn (Student $student): array => [
            'id' => (string) Str::ulid(),
            'kbm_id' => $this->kbm->id,
            'student_id' => $student->id,
            'status' => $status,
        ])->all();

        if ($payload === []) {
            return Notification::make()
                ->title('Tidak ada siswa untuk diabsen')
                ->warning()
                ->send();
        }

        DB::transaction(function () use ($payload): void {
            Attendance::upsert(
                $payload,
                uniqueBy: ['kbm_id', 'student_id'],
                update: ['status']
            );
        });

        $this->attendanceStatuses = $students
            ->mapWithKeys(fn (Student $student): array => [$student->id => $status])
            ->all();

        $this->resetTable();

        return Notification::make()
            ->title('Absensi berhasil diperbarui')
            ->body("Semua siswa ditandai {$this->attendanceOptions()[$status]}.")
            ->success()
            ->send();
    }

    private function loadAttendanceStatuses(): void
    {
        $this->attendanceStatuses = Attendance::query()
            ->where('kbm_id', $this->kbm->id)
            ->pluck('status', 'student_id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function attendanceOptions(): array
    {
        return [
            'HADIR' => 'Hadir',
            'SAKIT' => 'Sakit',
            'IZIN' => 'Izin',
            'ALPA' => 'Alpa',
        ];
    }
}
