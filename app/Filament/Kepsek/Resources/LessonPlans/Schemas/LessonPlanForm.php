<?php

namespace App\Filament\Kepsek\Resources\LessonPlans\Schemas;

use App\Models\LessonPlan;
use App\Support\PublicStorageUrl;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class LessonPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail RPP')
                    ->description('Kepsek hanya dapat mengubah status dan catatan perubahan.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('teacher_name')
                            ->label('Guru')
                            ->formatStateUsing(fn (?LessonPlan $record): string => $record?->teacher?->user?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('subject_name')
                            ->label('Mata Pelajaran')
                            ->formatStateUsing(fn (?LessonPlan $record): string => $record?->subjectForDisplay?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('class_name')
                            ->label('Kelas')
                            ->formatStateUsing(fn (?LessonPlan $record): string => $record?->schoolClass?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),
                        DatePicker::make('implementation_date')
                            ->label('Tanggal Pelaksanaan')
                            ->native(false)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('topic')
                            ->label('Judul RPP')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Placeholder::make('file_path_preview')
                            ->label('Dokumen RPP')
                            ->content(function (?LessonPlan $record): HtmlString|string {
                                if (blank($record?->file_path)) {
                                    return '-';
                                }

                                $url = PublicStorageUrl::fromPublicDiskPath($record->file_path);
                                $filename = basename($record->file_path);

                                return new HtmlString("<a href=\"{$url}\" target=\"_blank\" class=\"text-primary-600 hover:underline\">{$filename}</a>");
                            })
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'DRAFT' => 'Draft',
                                'PENDING' => 'Pending Approval',
                                'REVISED' => 'Revisi',
                                'APPROVED' => 'Approved',
                            ])
                            ->disableOptionWhen(fn (string $value): bool => in_array($value, ['DRAFT', 'PENDING'], true))
                            ->required()
                            ->native(false)
                            ->live(),
                        RichEditor::make('revision_note')
                            ->label('Catatan Perubahan')
                            ->required(fn (Get $get): bool => $get('status') === 'REVISED')
                            ->disabled(fn (Get $get): bool => $get('status') !== 'REVISED')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
