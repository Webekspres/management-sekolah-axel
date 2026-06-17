<?php

namespace App\Filament\Kepsek\Resources\Kbms\Schemas;

use App\Models\Kbm;
use App\Support\PublicStorageUrl;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class KbmForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Laporan KBM')
                    ->description('Kepsek hanya dapat mengubah status dan catatan perubahan.')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('date')
                            ->label('Tanggal KBM')
                            ->native(false)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('teacher_name')
                            ->label('Guru')
                            ->formatStateUsing(fn (?Kbm $record): string => $record?->schedule?->teacher?->user?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('class_name')
                            ->label('Kelas')
                            ->formatStateUsing(fn (?Kbm $record): string => $record?->schedule?->schoolClass?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('subject_name')
                            ->label('Mata Pelajaran')
                            ->formatStateUsing(fn (?Kbm $record): string => $record?->schedule?->subjectForDisplay?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('lesson_plan_topic')
                            ->label('RPP')
                            ->formatStateUsing(fn (?Kbm $record): string => $record?->lessonPlan?->topic ?? '-')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Textarea::make('process_note')
                            ->label('Catatan Proses Belajar')
                            ->rows(4)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Textarea::make('problem_note')
                            ->label('Kendala')
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Textarea::make('solution_note')
                            ->label('Solusi / Tindak Lanjut')
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Placeholder::make('documentation_path_preview')
                            ->label('Dokumentasi')
                            ->content(function (?Kbm $record): HtmlString|string {
                                if (blank($record?->documentation_path)) {
                                    return '-';
                                }

                                $url = PublicStorageUrl::fromPublicDiskPath($record->documentation_path);
                                $filename = basename($record->documentation_path);

                                return new HtmlString("<a href=\"{$url}\" target=\"_blank\" class=\"text-primary-600 hover:underline\">{$filename}</a>");
                            })
                            ->columnSpanFull(),
                        Placeholder::make('attendance_summary')
                            ->label('Ringkasan Kehadiran')
                            ->content(function (?Kbm $record): HtmlString|string {
                                if (! $record?->schedule?->schoolClass) {
                                    return '-';
                                }

                                $totalStudents = $record->schedule->schoolClass->students()
                                    ->withoutGlobalScopes()
                                    ->count();

                                $statusCounts = $record->attendances()
                                    ->selectRaw('status, COUNT(*) as total')
                                    ->groupBy('status')
                                    ->pluck('total', 'status');

                                $hadir = (int) ($statusCounts['HADIR'] ?? 0);
                                $sakit = (int) ($statusCounts['SAKIT'] ?? 0);
                                $izin = (int) ($statusCounts['IZIN'] ?? 0);
                                $alpa = (int) ($statusCounts['ALPA'] ?? 0);

                                $attendanceFilled = $hadir + $sakit + $izin + $alpa;
                                $completionPercentage = $totalStudents > 0
                                    ? (int) round(($attendanceFilled / $totalStudents) * 100)
                                    : 0;

                                return new HtmlString("
                                    <div class='space-y-3'>
                                        <div class='text-sm text-gray-700 dark:text-gray-300'>
                                            <span class='font-medium'>Total siswa:</span> {$totalStudents} &middot;
                                            <span class='font-medium'>Terisi:</span> {$attendanceFilled}/{$totalStudents} ({$completionPercentage}%)
                                        </div>
                                        <div class='grid grid-cols-2 gap-2 md:grid-cols-4'>
                                            <div class='rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm dark:border-success-700 dark:bg-success-950/30'>
                                                <div class='text-success-700 dark:text-success-300'>Hadir</div>
                                                <div class='text-lg font-semibold text-success-800 dark:text-success-200'>{$hadir}</div>
                                            </div>
                                            <div class='rounded-lg border border-warning-200 bg-warning-50 px-3 py-2 text-sm dark:border-warning-700 dark:bg-warning-950/30'>
                                                <div class='text-warning-700 dark:text-warning-300'>Sakit</div>
                                                <div class='text-lg font-semibold text-warning-800 dark:text-warning-200'>{$sakit}</div>
                                            </div>
                                            <div class='rounded-lg border border-info-200 bg-info-50 px-3 py-2 text-sm dark:border-info-700 dark:bg-info-950/30'>
                                                <div class='text-info-700 dark:text-info-300'>Izin</div>
                                                <div class='text-lg font-semibold text-info-800 dark:text-info-200'>{$izin}</div>
                                            </div>
                                            <div class='rounded-lg border border-danger-200 bg-danger-50 px-3 py-2 text-sm dark:border-danger-700 dark:bg-danger-950/30'>
                                                <div class='text-danger-700 dark:text-danger-300'>Alpa</div>
                                                <div class='text-lg font-semibold text-danger-800 dark:text-danger-200'>{$alpa}</div>
                                            </div>
                                        </div>
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'PENDING' => 'Pending Approval',
                                'REVISED' => 'Revisi',
                                'APPROVED' => 'Approved',
                            ])
                            ->disableOptionWhen(fn (string $value): bool => $value === 'PENDING')
                            ->required()
                            ->native(false)
                            ->live(),
                        Textarea::make('revision_note')
                            ->label('Catatan Perubahan')
                            ->rows(4)
                            ->required(fn (Get $get): bool => $get('status') === 'REVISED')
                            ->disabled(fn (Get $get): bool => $get('status') !== 'REVISED')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
