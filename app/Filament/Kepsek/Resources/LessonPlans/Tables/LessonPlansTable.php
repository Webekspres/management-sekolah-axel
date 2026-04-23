<?php

namespace App\Filament\Kepsek\Resources\LessonPlans\Tables;

use App\Models\LessonPlan;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['subject', 'teacher.user']))
            ->columns([
                TextColumn::make('teacher.user.name')
                    ->label('Guru')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('topic')
                    ->label('Topik')
                    ->searchable(),
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
                    ->toggleable()
                    ->limit(60),
                TextColumn::make('file_path')
                    ->label('File')
                    ->formatStateUsing(fn (string $state): string => basename($state))
                    ->url(fn (LessonPlan $record): string => Storage::url($record->file_path), shouldOpenInNewTab: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'PENDING' => 'Pending',
                        'REVISED' => 'Revisi',
                        'APPROVED' => 'Approved',
                    ]),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalHeading('Detail Pengajuan RPP')
                    ->modalContent(fn (LessonPlan $record) => view('filament.modals.lesson-plan-detail', [
                        'lessonPlan' => $record->loadMissing(['teacher.user', 'subject']),
                    ])),
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->color('success')
                    ->visible(fn (LessonPlan $record): bool => $record->status === 'PENDING')
                    ->action(function (LessonPlan $record): void {
                        try {
                            $record->approve(auth()->user());
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->successNotificationTitle('RPP disetujui.'),
                Action::make('requestRevision')
                    ->label('Minta Revisi')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->schema([
                        Textarea::make('revision_note')
                            ->label('Alasan Revisi')
                            ->required(false)
                            ->rules(['required', 'string', 'max:2000'])
                            ->markAsRequired(),
                    ])
                    ->visible(fn (LessonPlan $record): bool => $record->status === 'PENDING')
                    ->action(function (array $data, LessonPlan $record): void {
                        try {
                            $record->markAsRevised(
                                actor: auth()->user(),
                                revisionNote: $data['revision_note'],
                            );
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->successNotificationTitle('Permintaan revisi sudah dikirim ke guru.'),
            ]);
    }
}
