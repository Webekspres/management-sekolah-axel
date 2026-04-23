<?php

namespace App\Filament\Kepsek\Resources\Kbms\Tables;

use App\Models\Kbm;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class KbmsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'schedule.teacher.user',
                'schedule.schoolClass',
                'schedule.subject',
                'lessonPlan.subject',
            ]))
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('schedule.teacher.user.name')
                    ->label('Guru')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schedule.schoolClass.name')
                    ->label('Kelas')
                    ->searchable(),
                TextColumn::make('schedule.subject.name')
                    ->label('Mata Pelajaran')
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
                TextColumn::make('process_note')
                    ->label('Ringkasan Proses')
                    ->limit(80),
                TextColumn::make('documentation_path')
                    ->label('Dokumentasi')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? basename($state) : '-')
                    ->url(
                        fn (Kbm $record): ?string => filled($record->documentation_path) ? Storage::url($record->documentation_path) : null,
                        shouldOpenInNewTab: true
                    ),
                TextColumn::make('revision_note')
                    ->label('Catatan Revisi')
                    ->toggleable()
                    ->limit(60),
            ])
            ->defaultSort('date', 'desc')
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
                    ->modalHeading('Detail Laporan KBM')
                    ->form(fn (Kbm $record): array => [
                        Placeholder::make('date')
                            ->label('Tanggal')
                            ->content(fn (): string => $record->date->format('d M Y')),
                        Placeholder::make('teacher_name')
                            ->label('Guru')
                            ->content(fn (): string => $record->schedule?->teacher?->user?->name ?? '-'),
                        Placeholder::make('class_name')
                            ->label('Kelas')
                            ->content(fn (): string => $record->schedule?->schoolClass?->name ?? '-'),
                        Placeholder::make('subject_name')
                            ->label('Mata Pelajaran')
                            ->content(fn (): string => $record->schedule?->subject?->name ?? '-'),
                        Placeholder::make('lesson_plan_topic')
                            ->label('RPP')
                            ->content(fn (): string => $record->lessonPlan?->topic ?? '-'),
                        Placeholder::make('process_note')
                            ->label('Catatan Proses Belajar')
                            ->content(fn (): string => $record->process_note ?? '-'),
                        Placeholder::make('problem_note')
                            ->label('Kendala')
                            ->content(fn (): string => $record->problem_note ?? '-'),
                        Placeholder::make('solution_note')
                            ->label('Solusi/Tindak Lanjut')
                            ->content(fn (): string => $record->solution_note ?? '-'),
                        Placeholder::make('documentation_path')
                            ->label('Dokumentasi')
                            ->content(function (): string {
                                if (filled($record->documentation_path)) {
                                    return '<a href="' . Storage::url($record->documentation_path) . '" target="_blank" class="text-blue-600 hover:underline">' . basename($record->documentation_path) . '</a>';
                                }
                                return '-';
                            })
                            ->html(),
                        Placeholder::make('status')
                            ->label('Status')
                            ->content(fn (): string => $record->status ?? '-'),
                        Placeholder::make('revision_note')
                            ->label('Catatan Revisi')
                            ->content(fn (): string => $record->revision_note ?? '-'),
                    ]),
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->color('success')
                    ->visible(fn (Kbm $record): bool => $record->status === 'PENDING')
                    ->action(function (Kbm $record): void {
                        try {
                            $record->approve(auth()->user());
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->successNotificationTitle('Laporan KBM disetujui.'),
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
                    ->visible(fn (Kbm $record): bool => $record->status === 'PENDING')
                    ->action(function (array $data, Kbm $record): void {
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
                    ->successNotificationTitle('Permintaan revisi KBM sudah dikirim ke guru.'),
            ]);
    }
}
