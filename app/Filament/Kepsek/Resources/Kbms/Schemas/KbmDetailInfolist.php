<?php

namespace App\Filament\Kepsek\Resources\Kbms\Schemas;

use App\Support\PublicStorageUrl;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;

class KbmDetailInfolist
{
    public static function configure(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi KBM')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('date')
                            ->label('Tanggal KBM')
                            ->date('d M Y'),
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
                        TextEntry::make('schedule.teacher.user.name')
                            ->label('Guru'),
                        TextEntry::make('schedule.schoolClass.name')
                            ->label('Kelas'),
                        TextEntry::make('schedule.subjectForDisplay.name')
                            ->label('Mata Pelajaran'),
                        TextEntry::make('lessonPlan.topic')
                            ->label('RPP'),
                    ]),
                Section::make('Catatan Proses Belajar')
                    ->schema([
                        TextEntry::make('process_note')
                            ->label('')
                            ->prose()
                            ->columnSpanFull(),
                    ]),
                Section::make('Kendala')
                    ->visible(fn ($record) => filled($record->problem_note))
                    ->schema([
                        TextEntry::make('problem_note')
                            ->label('')
                            ->prose()
                            ->columnSpanFull(),
                    ]),
                Section::make('Solusi / Tindak Lanjut')
                    ->visible(fn ($record) => filled($record->solution_note))
                    ->schema([
                        TextEntry::make('solution_note')
                            ->label('')
                            ->prose()
                            ->columnSpanFull(),
                    ]),
                Section::make('Catatan Revisi')
                    ->visible(fn ($record) => filled($record->revision_note))
                    ->schema([
                        TextEntry::make('revision_note')
                            ->label('')
                            ->prose()
                            ->columnSpanFull(),
                    ]),
                Section::make('Dokumentasi')
                    ->visible(fn ($record) => filled($record->documentation_path))
                    ->schema([
                        TextEntry::make('documentation_path')
                            ->label('')
                            ->formatStateUsing(fn (string $state): string => basename($state))
                            ->url(fn ($record): string => PublicStorageUrl::fromPublicDiskPath($record->documentation_path), shouldOpenInNewTab: true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
