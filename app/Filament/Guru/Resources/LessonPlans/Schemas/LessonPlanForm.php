<?php

namespace App\Filament\Guru\Resources\LessonPlans\Schemas;

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
                            ->markAsRequired(),
                        TextInput::make('topic')
                            ->label('Materi Topik')
                            ->required(false)
                            ->rules(['required', 'string', 'max:255'])
                            ->markAsRequired(),
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
                            ->columnSpanFull(),
                        Placeholder::make('status')
                            ->label('Status')
                            ->content(fn (?string $state): string => $state ?? 'DRAFT')
                            ->hiddenOn('create'),
                        Placeholder::make('revision_note')
                            ->label('Catatan Revisi dari Kepsek')
                            ->content(function (?string $state): string {
                                if (blank($state)) {
                                    return '-';
                                }

                                return $state;
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
}
