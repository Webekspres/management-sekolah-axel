<?php

namespace App\Filament\Guru\Resources\LessonPlans\Tables;

use App\Models\LessonPlan;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class LessonPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->with(['schoolClass', 'subjectForDisplay', 'teacher.user']);
            })
            ->columns([
                TextColumn::make('subjectForDisplay.name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('topic')
                    ->label('Topik')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schoolClass.name')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('implementation_date')
                    ->label('Tanggal Pelaksanaan')
                    ->date('d M Y')
                    ->sortable(),
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
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('file_path')
                    ->label('File')
                    ->formatStateUsing(fn (string $state): string => basename($state))
                    ->url(fn (LessonPlan $record): string => Storage::url($record->file_path), shouldOpenInNewTab: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
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
                    ->visible(fn (LessonPlan $record): bool => in_array($record->status, ['DRAFT', 'REVISED'], true))
                    ->action(function (LessonPlan $record): void {
                        try {
                            $record->submitForApproval(auth()->user());
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->successNotificationTitle('RPP berhasil diajukan ke kepala sekolah.'),
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->form(fn (LessonPlan $record): array => [
                        Placeholder::make('subject')
                            ->label('Mata Pelajaran')
                            ->content($record->subjectForDisplay?->name ?? '-'),
                        Placeholder::make('class')
                            ->label('Kelas')
                            ->content($record->schoolClass?->name ?? '-'),
                        Placeholder::make('topic')
                            ->label('Topik')
                            ->content($record->topic ?? '-'),
                        Placeholder::make('implementation_date')
                            ->label('Tanggal Pelaksanaan')
                            ->content($record->implementation_date?->format('d M Y') ?? '-'),
                        Placeholder::make('status')
                            ->label('Status')
                            ->content($record->status),
                        Placeholder::make('revision_note')
                            ->label('Catatan Revisi')
                            ->content(RichText::display($record->revision_note)),
                        Placeholder::make('file')
                            ->label('Dokumen')
                            ->content(filled($record->file_path) ? basename($record->file_path) : '-'),
                    ]),
                EditAction::make()
                    ->label('Detail / Edit'),
                DeleteAction::make()
                    ->visible(fn (LessonPlan $record): bool => $record->status !== 'APPROVED'),
            ]);
    }
}
