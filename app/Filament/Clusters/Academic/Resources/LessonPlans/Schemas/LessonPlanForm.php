<?php

namespace App\Filament\Clusters\Academic\Resources\LessonPlans\Schemas;

use App\Models\SchoolClass;
use App\Models\Teacher;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LessonPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Formulir RPP')
                    ->description('Pengajuan RPP dipublikasikan ke portal siswa/orang tua hanya setelah disetujui kepala sekolah.')
                    ->columns(2)
                    ->schema([
                        Select::make('teacher_id')
                            ->label('Guru Pengaju')
                            ->options(fn (): array => Teacher::query()
                                ->with('user')
                                ->get()
                                ->mapWithKeys(fn (Teacher $teacher): array => [
                                    $teacher->id => $teacher->user?->name ?? '-',
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('subject_id')
                            ->label('Mata Pelajaran')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('class_id')
                            ->label('Kelas')
                            ->options(fn (): array => SchoolClass::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('implementation_date')
                            ->label('Tanggal Pelaksanaan')
                            ->native(false)
                            ->required(),
                        TextInput::make('topic')
                            ->label('Judul RPP')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        FileUpload::make('file_path')
                            ->label('Dokumen RPP')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->directory('lesson-plans')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
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
                        Textarea::make('revision_note')
                            ->label('Catatan Approval')
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
