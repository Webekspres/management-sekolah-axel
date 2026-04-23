<?php

namespace App\Filament\Guru\Resources\LessonPlans\Tables;

use App\Models\LessonPlan;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
                return $query->with(['subject', 'teacher.user']);
            })
            ->columns([
                TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('topic')
                    ->label('Topik')
                    ->searchable()
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
                EditAction::make()
                    ->visible(fn (LessonPlan $record): bool => in_array($record->status, ['DRAFT', 'REVISED'], true)),
                DeleteAction::make()
                    ->visible(fn (LessonPlan $record): bool => $record->status === 'DRAFT'),
            ]);
    }
}
