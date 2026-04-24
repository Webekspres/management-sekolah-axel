<?php

namespace App\Filament\Clusters\Academic\Resources\LessonPlans\Tables;

use App\Models\LessonPlan;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LessonPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('topic')
                    ->label('Judul RPP')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('teacher.user.name')
                    ->label('Guru Pengaju')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subjectForDisplay.name')
                    ->label('Mata Pelajaran')
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
                    ->label('Status Approval')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'DRAFT' => 'Draft',
                        'PENDING' => 'Pending Approval',
                        'APPROVED' => 'Approved',
                        'REVISED' => 'Rejected',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'DRAFT' => 'gray',
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REVISED' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('file_path')
                    ->label('Dokumen')
                    ->formatStateUsing(fn (string $state): string => basename($state))
                    ->url(fn (LessonPlan $record): string => Storage::url($record->file_path), shouldOpenInNewTab: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Approval')
                    ->options([
                        'DRAFT' => 'Draft',
                        'PENDING' => 'Pending Approval',
                        'APPROVED' => 'Approved',
                        'REVISED' => 'Rejected',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (LessonPlan $record): bool => self::canApprove() && $record->status === 'PENDING')
                    ->action(function (LessonPlan $record): void {
                        try {
                            $record->approve(Auth::user());
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->successNotificationTitle('RPP berhasil disetujui.'),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->schema([
                        Textarea::make('revision_note')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->maxLength(2000)
                            ->rows(3),
                    ])
                    ->visible(fn (LessonPlan $record): bool => self::canApprove() && $record->status === 'PENDING')
                    ->action(function (array $data, LessonPlan $record): void {
                        try {
                            $record->markAsRevised(
                                actor: Auth::user(),
                                revisionNote: $data['revision_note'],
                            );
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->successNotificationTitle('RPP ditolak dan dikembalikan ke guru.'),
                DeleteAction::make()
                    ->visible(fn (LessonPlan $record): bool => in_array($record->status, ['DRAFT', 'REVISED'], true)),
            ]);
    }

    private static function canApprove(): bool
    {
        $role = Auth::user()?->effectiveRole();

        return in_array($role, ['super_admin', 'kepala_sekolah'], true);
    }
}
