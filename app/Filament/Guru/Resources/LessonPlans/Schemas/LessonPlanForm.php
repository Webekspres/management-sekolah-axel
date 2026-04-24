<?php

namespace App\Filament\Guru\Resources\LessonPlans\Schemas;

use App\Models\LessonPlan;
use App\Models\SchoolClass;
use App\Support\RichText;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class LessonPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Formulir RPP')
                    ->description('Susun RPP, simpan sebagai draft, lalu ajukan untuk approval.')
                    ->columns(2)
                    ->schema([
                        Select::make('subject_id')
                            ->label('Mata Pelajaran')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload()
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired()
                            ->disabled(fn (?LessonPlan $record): bool => self::isContentLocked($record)),
                        Select::make('class_id')
                            ->label('Kelas')
                            ->options(fn (): array => SchoolClass::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired()
                            ->disabled(fn (?LessonPlan $record): bool => self::isContentLocked($record)),
                        DatePicker::make('implementation_date')
                            ->label('Tanggal Pelaksanaan')
                            ->native(false)
                            ->required(false)
                            ->rules(['required', 'date'])
                            ->markAsRequired()
                            ->disabled(fn (?LessonPlan $record): bool => self::isContentLocked($record)),
                        TextInput::make('topic')
                            ->label('Materi Topik')
                            ->required(false)
                            ->rules(['required', 'string', 'max:255'])
                            ->markAsRequired()
                            ->disabled(fn (?LessonPlan $record): bool => self::isContentLocked($record)),
                        FileUpload::make('file_path')
                            ->label('File RPP')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->directory('lesson-plans')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired()
                            ->disabled(fn (?LessonPlan $record): bool => self::isContentLocked($record))
                            ->columnSpanFull(),
                        Placeholder::make('status')
                            ->label('Status')
                            ->content(fn (?string $state): string => $state ?? 'DRAFT')
                            ->hiddenOn('create'),
                        Placeholder::make('revision_note')
                            ->label('Catatan Revisi dari Kepsek')
                            ->content(function (?string $state): string {
                                return RichText::display($state);
                            })
                            ->hiddenOn('create'),
                        Placeholder::make('file_link')
                            ->label('File Saat Ini')
                            ->content(function (?string $state): string {
                                if (blank($state)) {
                                    return '-';
                                }

                                return Storage::url($state);
                            })
                            ->hiddenOn('create')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function isContentLocked(?LessonPlan $record): bool
    {
        if (! $record) {
            return false;
        }

        return in_array($record->status, ['REVISED', 'APPROVED'], true);
    }
}
