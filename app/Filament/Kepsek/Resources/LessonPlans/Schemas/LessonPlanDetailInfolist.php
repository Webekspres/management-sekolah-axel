<?php

namespace App\Filament\Kepsek\Resources\LessonPlans\Schemas;

use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;

class LessonPlanDetailInfolist
{
    public static function configure(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi RPP')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('teacher.user.name')
                            ->label('Guru'),
                        TextEntry::make('subjectForDisplay.name')
                            ->label('Mata Pelajaran'),
                        TextEntry::make('topic')
                            ->label('Topik / Materi')
                            ->columnSpanFull(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'DRAFT' => 'gray',
                                'PENDING' => 'warning',
                                'REVISED' => 'danger',
                                'APPROVED' => 'success',
                                default => 'gray',
                            }),
                    ]),
                Section::make('Catatan Revisi')
                    ->visible(fn ($record) => filled($record->revision_note))
                    ->schema([
                        TextEntry::make('revision_note')
                            ->label('')
                            ->prose()
                            ->columnSpanFull(),
                    ]),
                Section::make('File RPP')
                    ->schema([
                        TextEntry::make('file_path')
                            ->label('')
                            ->formatStateUsing(fn (string $state): string => basename($state))
                            ->url(fn ($record): string => Storage::url($record->file_path), shouldOpenInNewTab: true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
