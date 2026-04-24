<?php

namespace App\Filament\Guru\Resources\Kbms\Tables;

use App\Models\Kbm;
use App\Support\RichText;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KbmsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'schedule.schoolClass',
                'schedule.subjectForDisplay',
                'lessonPlan.subjectForDisplay',
            ]))
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('schedule.schoolClass.name')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schedule.subjectForDisplay.name')
                    ->label('Mata Pelajaran')
                    ->searchable(),
                TextColumn::make('lessonPlan.topic')
                    ->label('RPP')
                    ->limit(40),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DRAFT' => 'gray',
                        'PENDING' => 'warning',
                        'REVISED' => 'danger',
                        'APPROVED' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('revision_note')
                    ->label('Catatan Revisi')
                    ->formatStateUsing(fn (?string $state): string => RichText::display($state))
                    ->toggleable()
                    ->limit(60),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'PENDING' => 'Pending',
                        'REVISED' => 'Revisi',
                        'APPROVED' => 'Approved',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                Action::make('submit')
                    ->label('Ajukan')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->visible(fn (Kbm $record): bool => in_array($record->status, ['DRAFT', 'REVISED'], true))
                    ->action(function (Kbm $record): void {
                        try {
                            $record->submitForApproval(auth()->user());
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->successNotificationTitle('Laporan KBM berhasil diajukan ke kepala sekolah.'),
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->form(fn (Kbm $record): array => [
                        Placeholder::make('date')
                            ->label('Tanggal')
                            ->content($record->date?->format('d M Y') ?? '-'),
                        Placeholder::make('class')
                            ->label('Kelas')
                            ->content($record->schedule?->schoolClass?->name ?? '-'),
                        Placeholder::make('subject')
                            ->label('Mata Pelajaran')
                            ->content($record->schedule?->subjectForDisplay?->name ?? '-'),
                        Placeholder::make('lesson_plan')
                            ->label('RPP')
                            ->content($record->lessonPlan?->topic ?? '-'),
                        Placeholder::make('status')
                            ->label('Status')
                            ->content($record->status),
                        Placeholder::make('process_note')
                            ->label('Catatan Proses')
                            ->content($record->process_note ?? '-'),
                        Placeholder::make('problem_note')
                            ->label('Kendala')
                            ->content($record->problem_note ?: '-'),
                        Placeholder::make('solution_note')
                            ->label('Solusi / Tindak Lanjut')
                            ->content($record->solution_note ?: '-'),
                        Placeholder::make('revision_note')
                            ->label('Catatan Revisi')
                            ->content(RichText::display($record->revision_note)),
                    ]),
                EditAction::make()
                    ->label('Detail / Edit'),
                DeleteAction::make()
                    ->visible(fn (Kbm $record): bool => $record->status !== 'APPROVED'),
            ]);
    }
}
