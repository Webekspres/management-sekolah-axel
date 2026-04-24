<?php

namespace App\Filament\Kepsek\Resources\Kbms\Schemas;

use App\Models\Kbm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
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

                                $url = Storage::url($record->documentation_path);
                                $filename = basename($record->documentation_path);

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
