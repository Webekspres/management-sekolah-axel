<?php

namespace App\Filament\Guru\Resources\Kbms\Schemas;

use App\Models\LessonPlan;
use App\Models\Schedule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KbmForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Laporan KBM Harian')
                    ->description('Isi laporan pelaksanaan KBM lalu ajukan untuk approval kepala sekolah.')
                    ->columns(2)
                    ->schema([
                        Select::make('schedule_id')
                            ->label('Jadwal')
                            ->options(function (): array {
                                $teacherId = auth()->user()?->teacher?->id;

                                if (! $teacherId) {
                                    return [];
                                }

                                return Schedule::query()
                                    ->with(['schoolClass', 'subject'])
                                    ->where('teacher_id', $teacherId)
                                    ->orderBy('day_of_week')
                                    ->get()
                                    ->mapWithKeys(fn (Schedule $schedule) => [
                                        $schedule->id => "{$schedule->schoolClass?->name} - {$schedule->subject?->name} ({$schedule->start_time}-{$schedule->end_time})",
                                    ])
                                    ->all();
                            })
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired()
                            ->searchable(),
                        Select::make('lesson_plan_id')
                            ->label('RPP Approved')
                            ->options(function (): array {
                                $teacherId = auth()->user()?->teacher?->id;

                                if (! $teacherId) {
                                    return [];
                                }

                                return LessonPlan::query()
                                    ->where('teacher_id', $teacherId)
                                    ->where('status', 'APPROVED')
                                    ->orderByDesc('id')
                                    ->get()
                                    ->mapWithKeys(fn (LessonPlan $lessonPlan) => [
                                        $lessonPlan->id => "{$lessonPlan->topic} ({$lessonPlan->subject?->name})",
                                    ])
                                    ->all();
                            })
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired()
                            ->searchable(),
                        DatePicker::make('date')
                            ->label('Tanggal KBM')
                            ->required(false)
                            ->rules(['required', 'date'])
                            ->markAsRequired(),
                        Textarea::make('process_note')
                            ->label('Catatan Proses Belajar')
                            ->rows(5)
                            ->required(false)
                            ->rules(['required', 'string'])
                            ->markAsRequired()
                            ->columnSpanFull(),
                        Textarea::make('problem_note')
                            ->label('Kendala')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                        Textarea::make('solution_note')
                            ->label('Solusi/Tindak Lanjut')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                        FileUpload::make('documentation_path')
                            ->label('Dokumentasi KBM')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->directory('kbm-documentations')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->nullable()
                            ->columnSpanFull(),
                        Placeholder::make('status')
                            ->label('Status')
                            ->content(fn (?string $state): string => $state ?? 'DRAFT')
                            ->hiddenOn('create'),
                        Placeholder::make('revision_note')
                            ->label('Catatan Revisi dari Kepsek')
                            ->content(fn (?string $state): string => filled($state) ? $state : '-')
                            ->hiddenOn('create')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
