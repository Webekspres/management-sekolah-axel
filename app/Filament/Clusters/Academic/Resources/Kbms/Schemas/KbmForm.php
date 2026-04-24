<?php

namespace App\Filament\Clusters\Academic\Resources\Kbms\Schemas;

use App\Models\LessonPlan;
use App\Models\Schedule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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
                    ->description('Laporan KBM hanya dipublikasikan ke portal siswa/orang tua setelah disetujui kepala sekolah.')
                    ->columns(2)
                    ->schema([
                        Select::make('schedule_id')
                            ->label('Jadwal')
                            ->relationship(
                                name: 'schedule',
                                titleAttribute: 'id',
                                modifyQueryUsing: fn ($query) => $query
                                    ->with(['teacher.user', 'schoolClass', 'subjectForDisplay'])
                                    ->orderBy('day_of_week')
                                    ->orderBy('start_time'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (Schedule $record): string => "{$record->teacher?->user?->name} - {$record->schoolClass?->name} - {$record->subjectForDisplay?->name} ({$record->start_time}-{$record->end_time})"
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('lesson_plan_id')
                            ->label('RPP Approved')
                            ->relationship(
                                name: 'lessonPlan',
                                titleAttribute: 'topic',
                                modifyQueryUsing: fn ($query) => $query
                                    ->where('status', 'APPROVED')
                                    ->with(['teacher.user', 'subjectForDisplay'])
                                    ->orderByDesc('id'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (LessonPlan $record): string => "{$record->topic} ({$record->teacher?->user?->name} - {$record->subjectForDisplay?->name})"
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('date')
                            ->label('Tanggal KBM')
                            ->native(false)
                            ->required(),
                        Select::make('status')
                            ->label('Status Approval')
                            ->options([
                                'DRAFT' => 'Draft',
                                'PENDING' => 'Pending Approval',
                                'APPROVED' => 'Approved',
                                'REVISED' => 'Rejected',
                            ])
                            ->default('DRAFT')
                            ->required(),
                        Textarea::make('process_note')
                            ->label('Catatan Proses Belajar')
                            ->rows(5)
                            ->required()
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
                        Textarea::make('revision_note')
                            ->label('Catatan Approval')
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
