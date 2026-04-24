<?php

namespace App\Filament\Guru\Resources\Kbms\Schemas;

use App\Models\Kbm;
use App\Models\LessonPlan;
use App\Models\Schedule;
use App\Support\RichText;
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
                            ->relationship(
                                name: 'schedule',
                                titleAttribute: 'id',
                                modifyQueryUsing: fn ($query) => $query
                                    ->where('teacher_id', auth()->user()?->teacher?->id)
                                    ->with(['schoolClass', 'subjectForDisplay'])
                                    ->orderBy('day_of_week')
                                    ->orderBy('start_time'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Schedule $record) => "{$record->schoolClass?->name} - {$record->subjectForDisplay?->name} ({$record->start_time}-{$record->end_time})")
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired()
                            ->searchable()
                            ->disabled(fn (?Kbm $record): bool => self::isContentLocked($record)),
                        Select::make('lesson_plan_id')
                            ->label('RPP Approved')
                            ->relationship(
                                name: 'lessonPlan',
                                titleAttribute: 'id',
                                modifyQueryUsing: fn ($query) => $query
                                    ->where('teacher_id', auth()->user()?->teacher?->id)
                                    ->where('status', 'APPROVED')
                                    ->with('subjectForDisplay')
                                    ->orderByDesc('id'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (LessonPlan $record) => "{$record->topic} ({$record->subjectForDisplay?->name})")
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired()
                            ->searchable()
                            ->disabled(fn (?Kbm $record): bool => self::isContentLocked($record)),
                        DatePicker::make('date')
                            ->label('Tanggal KBM')
                            ->required(false)
                            ->rules(['required', 'date'])
                            ->markAsRequired()
                            ->disabled(fn (?Kbm $record): bool => self::isContentLocked($record)),
                        Textarea::make('process_note')
                            ->label('Catatan Proses Belajar')
                            ->rows(5)
                            ->required(false)
                            ->rules(['required', 'string'])
                            ->markAsRequired()
                            ->disabled(fn (?Kbm $record): bool => self::isContentLocked($record))
                            ->columnSpanFull(),
                        Textarea::make('problem_note')
                            ->label('Kendala')
                            ->rows(3)
                            ->nullable()
                            ->disabled(fn (?Kbm $record): bool => self::isContentLocked($record))
                            ->columnSpanFull(),
                        Textarea::make('solution_note')
                            ->label('Solusi/Tindak Lanjut')
                            ->rows(3)
                            ->nullable()
                            ->disabled(fn (?Kbm $record): bool => self::isContentLocked($record))
                            ->columnSpanFull(),
                        FileUpload::make('documentation_path')
                            ->label('Dokumentasi KBM')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->directory('kbm-documentations')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->nullable()
                            ->disabled(fn (?Kbm $record): bool => self::isContentLocked($record))
                            ->columnSpanFull(),
                        Placeholder::make('status')
                            ->label('Status')
                            ->content(fn (?string $state): string => $state ?? 'DRAFT')
                            ->hiddenOn('create'),
                        Placeholder::make('revision_note')
                            ->label('Catatan Revisi dari Kepsek')
                            ->content(fn (?string $state): string => RichText::display($state))
                            ->hiddenOn('create')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function isContentLocked(?Kbm $record): bool
    {
        if (! $record) {
            return false;
        }

        return in_array($record->status, ['REVISED', 'APPROVED'], true);
    }
}
