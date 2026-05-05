<?php

namespace App\Filament\Guru\Resources\Rapors\Tables;

use App\Models\Rapor;
use App\Services\RaporService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class RaporsTable
{
    public static function configureForGuru(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['student.user', 'academicYear']))
            ->columns([
                TextColumn::make('student.user.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('academicYear.name')
                    ->label('Tahun Akademik')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DRAFT' => 'gray',
                        'FINALIZED' => 'warning',
                        'APPROVED' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'DRAFT' => 'Draft',
                        'FINALIZED' => 'Terfinalisasi',
                        'APPROVED' => 'Disetujui',
                        default => $state,
                    }),
                TextColumn::make('rejection_note')
                    ->label('Catatan Penolakan')
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('approved_at')
                    ->label('Disetujui Pada')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('student.user.name', 'asc')
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'FINALIZED' => 'Terfinalisasi',
                        'APPROVED' => 'Disetujui',
                    ]),
            ])
            ->recordActions([
                Action::make('finalize')
                    ->label('Finalisasi')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('warning')
                    ->visible(fn (Rapor $record): bool => $record->isDraft())
                    ->requiresConfirmation()
                    ->modalHeading('Finalisasi Rapor')
                    ->modalDescription('Pastikan semua nilai sudah lengkap. Setelah difinalisasi, nilai tidak dapat diubah sampai Kepala Sekolah menyetujui atau menolak.')
                    ->action(function (Rapor $record): void {
                        $service = app(RaporService::class);
                        $missing = $service->validateCompleteness($record);

                        if (! empty($missing)) {
                            Notification::make()
                                ->title('Rapor belum lengkap')
                                ->body('Komponen yang kurang: '.implode(', ', $missing))
                                ->danger()
                                ->send();

                            return;
                        }

                        $service->finalizeRapor($record);

                        Notification::make()
                            ->title('Rapor berhasil difinalisasi')
                            ->success()
                            ->send();
                    }),

                Action::make('revert')
                    ->label('Batalkan Finalisasi')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->visible(fn (Rapor $record): bool => $record->isFinalized())
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Finalisasi Rapor')
                    ->modalDescription('Rapor akan dikembalikan ke status Draft dan dapat diedit kembali.')
                    ->schema([
                        Textarea::make('revert_note')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Rapor $record, array $data): void {
                        app(RaporService::class)->rejectRapor($record, $data['revert_note']);

                        Notification::make()
                            ->title('Finalisasi dibatalkan')
                            ->success()
                            ->send();
                    }),

                Action::make('generate_pdf')
                    ->label('Generate PDF')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->color('primary')
                    ->visible(fn (Rapor $record): bool => ! $record->isApproved())
                    ->action(function (Rapor $record): void {
                        try {
                            app(RaporService::class)->generatePdf($record);

                            Notification::make()
                                ->title('PDF berhasil digenerate')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal generate PDF')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('success')
                    ->visible(fn (Rapor $record): bool => $record->file_path !== null && Storage::exists($record->file_path))
                    ->url(fn (Rapor $record): string => route('rapor.download', $record->id))
                    ->openUrlInNewTab(),
            ]);
    }
}
