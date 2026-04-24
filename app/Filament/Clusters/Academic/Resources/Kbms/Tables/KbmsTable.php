<?php

namespace App\Filament\Clusters\Academic\Resources\Kbms\Tables;

use App\Models\Kbm;
use App\Support\RichText;
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

class KbmsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('schedule.teacher.user.name')
                    ->label('Guru Pengaju')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schedule.schoolClass.name')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schedule.subjectForDisplay.name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lessonPlan.topic')
                    ->label('RPP')
                    ->searchable()
                    ->limit(40),
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
                TextColumn::make('documentation_path')
                    ->label('Dokumentasi')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? basename($state) : '-')
                    ->url(
                        fn (Kbm $record): ?string => filled($record->documentation_path) ? Storage::url($record->documentation_path) : null,
                        shouldOpenInNewTab: true,
                    ),
                TextColumn::make('revision_note')
                    ->label('Catatan Revisi')
                    ->formatStateUsing(fn (?string $state): string => RichText::display($state))
                    ->toggleable()
                    ->limit(60),
            ])
            ->defaultSort('date', 'desc')
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
                    ->visible(fn (Kbm $record): bool => self::canApprove() && $record->status === 'PENDING')
                    ->action(function (Kbm $record): void {
                        try {
                            $record->approve(Auth::user());
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->successNotificationTitle('Laporan KBM berhasil disetujui.'),
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
                    ->visible(fn (Kbm $record): bool => self::canApprove() && $record->status === 'PENDING')
                    ->action(function (array $data, Kbm $record): void {
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
                    ->successNotificationTitle('Laporan KBM ditolak dan dikembalikan ke guru.'),
                DeleteAction::make()
                    ->visible(fn (Kbm $record): bool => in_array($record->status, ['DRAFT', 'REVISED'], true)),
            ]);
    }

    private static function canApprove(): bool
    {
        $role = Auth::user()?->effectiveRole();

        return in_array($role, ['super_admin', 'kepala_sekolah'], true);
    }
}
