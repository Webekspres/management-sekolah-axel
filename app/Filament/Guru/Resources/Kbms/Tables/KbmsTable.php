<?php

namespace App\Filament\Guru\Resources\Kbms\Tables;

use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Student;
use App\Support\RichText;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class KbmsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'schedule.schoolClass.students',
                'schedule.subjectForDisplay',
                'lessonPlan.subjectForDisplay',
                'attendances',
            ]))
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('schedule.schoolClass.name')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schedule.subjectForDisplay.name')
                    ->label('Mata Pelajaran')
                    ->searchable(),
                TextColumn::make('lessonPlan.topic')
                    ->label('RPP')
                    ->limit(40),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DRAFT' => 'gray',
                        'PENDING' => 'warning',
                        'REVISED' => 'danger',
                        'APPROVED' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('attendance_status')
                    ->label('Status Absensi')
                    ->state(function (Kbm $record): string {
                        $attendedCount = $record->attendances->count();
                        $totalCount = $record->schedule->schoolClass->students()->withoutGlobalScopes()->count();

                        if ($totalCount === 0) {
                            return 'Tidak ada siswa';
                        }

                        if ($attendedCount >= $totalCount) {
                            return 'Lengkap ✓';
                        }

                        return "{$attendedCount}/{$totalCount} diabsen";
                    })
                    ->badge()
                    ->color(fn (string $state): string => str_contains($state, 'Lengkap') ? 'success' : 'warning'),
                TextColumn::make('revision_note')
                    ->label('Catatan Revisi')
                    ->formatStateUsing(fn (?string $state): string => RichText::display($state))
                    ->toggleable()
                    ->limit(60),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'PENDING' => 'Pending',
                        'REVISED' => 'Revisi',
                        'APPROVED' => 'Approved',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                Action::make('input_absensi')
                    ->label('Input Absensi')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->modalHeading(fn (Kbm $record): string => "Input Absensi — {$record->schedule->schoolClass->name}")
                    ->modalSubmitActionLabel('Simpan Absensi')
                    ->fillForm(function (Kbm $record): array {
                        $students = Student::withoutGlobalScopes()
                            ->where('class_id', $record->schedule->schoolClass->id)
                            ->get();

                        $existingAttendances = Attendance::where('kbm_id', $record->id)
                            ->pluck('status', 'student_id');

                        return [
                            'students' => $students->map(fn (Student $student): array => [
                                'student_id' => $student->id,
                                'status' => $existingAttendances->get($student->id, 'HADIR'),
                            ])->values()->all(),
                        ];
                    })
                    ->form(function (Kbm $record): array {
                        $students = Student::withoutGlobalScopes()
                            ->where('class_id', $record->schedule->schoolClass->id)
                            ->get();

                        if ($students->isEmpty()) {
                            return [
                                Placeholder::make('empty_message')
                                    ->label('')
                                    ->content('Tidak ada siswa terdaftar di kelas ini.'),
                            ];
                        }

                        $components = [];

                        foreach ($students as $index => $student) {
                            $components[] = Placeholder::make("students_{$index}_name")
                                ->label('Nama Siswa')
                                ->content($student->user?->name ?? '-');

                            $components[] = Hidden::make("students.{$index}.student_id")
                                ->default($student->id);

                            $components[] = Select::make("students.{$index}.status")
                                ->label('Status')
                                ->options([
                                    'HADIR' => 'Hadir',
                                    'SAKIT' => 'Sakit',
                                    'IZIN' => 'Izin',
                                    'ALPA' => 'Alpa',
                                ])
                                ->required()
                                ->default('HADIR');
                        }

                        return $components;
                    })
                    ->action(function (array $data, Kbm $record): void {
                        try {
                            $upsertData = collect($data['students'] ?? [])
                                ->map(fn (array $row): array => [
                                    'id' => (string) Str::ulid(),
                                    'kbm_id' => $record->id,
                                    'student_id' => $row['student_id'],
                                    'status' => $row['status'],
                                ])
                                ->all();

                            DB::transaction(function () use ($upsertData): void {
                                Attendance::upsert(
                                    $upsertData,
                                    uniqueBy: ['kbm_id', 'student_id'],
                                    update: ['status'],
                                );
                            });

                            $count = count($upsertData);

                            Notification::make()
                                ->title('Absensi berhasil disimpan')
                                ->body("{$count} record absensi telah disimpan.")
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Log::error('Bulk attendance save failed', [
                                'kbm_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Gagal menyimpan absensi')
                                ->body('Terjadi kesalahan. Semua perubahan dibatalkan.')
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('submit')
                    ->label('Ajukan')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->visible(fn (Kbm $record): bool => in_array($record->status, ['DRAFT', 'REVISED'], true))
                    ->action(function (Kbm $record): void {
                        try {
                            $record->submitForApproval(auth()->user());
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->successNotificationTitle('Laporan KBM berhasil diajukan ke kepala sekolah.'),
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->form(fn (Kbm $record): array => [
                        Placeholder::make('date')
                            ->label('Tanggal')
                            ->content($record->date?->format('d M Y') ?? '-'),
                        Placeholder::make('class')
                            ->label('Kelas')
                            ->content($record->schedule?->schoolClass?->name ?? '-'),
                        Placeholder::make('subject')
                            ->label('Mata Pelajaran')
                            ->content($record->schedule?->subjectForDisplay?->name ?? '-'),
                        Placeholder::make('lesson_plan')
                            ->label('RPP')
                            ->content($record->lessonPlan?->topic ?? '-'),
                        Placeholder::make('status')
                            ->label('Status')
                            ->content($record->status),
                        Placeholder::make('process_note')
                            ->label('Catatan Proses')
                            ->content($record->process_note ?? '-'),
                        Placeholder::make('problem_note')
                            ->label('Kendala')
                            ->content($record->problem_note ?: '-'),
                        Placeholder::make('solution_note')
                            ->label('Solusi / Tindak Lanjut')
                            ->content($record->solution_note ?: '-'),
                        Placeholder::make('revision_note')
                            ->label('Catatan Revisi')
                            ->content(RichText::display($record->revision_note)),
                    ]),
                EditAction::make()
                    ->label('Detail / Edit'),
            ]);
    }
}
