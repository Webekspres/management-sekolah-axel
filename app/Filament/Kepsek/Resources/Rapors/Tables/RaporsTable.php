<?php

namespace App\Filament\Kepsek\Resources\Rapors\Tables;

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
    public static function configureForKepsek(Table $table): Table
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
                        'FINALIZED' => 'Menunggu Persetujuan',
                        'APPROVED' => 'Disetujui',
                        default => $state,
                    }),
                TextColumn::make('approved_at')
                    ->label('Disetujui Pada')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('rejection_note')
                    ->label('Catatan Penolakan')
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('status', 'asc')
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'FINALIZED' => 'Menunggu Persetujuan',
                        'APPROVED' => 'Disetujui',
                    ]),
                SelectFilter::make('academic_year_id')
                    ->label('Tahun Akademik')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Rapor $record): bool => $record->isFinalized())
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Rapor')
                    ->modalDescription('Rapor akan disetujui dan tidak dapat diubah lagi oleh guru.')
                    ->action(function (Rapor $record): void {
                        app(RaporService::class)->approveRapor($record);

                        Notification::make()
                            ->title('Rapor berhasil disetujui')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Rapor $record): bool => $record->isFinalized())
                    ->schema([
                        Textarea::make('rejection_note')
                            ->label('Catatan Penolakan')
                            ->required()
                            ->rows(3)
                            ->placeholder('Jelaskan alasan penolakan...'),
                    ])
                    ->action(function (Rapor $record, array $data): void {
                        app(RaporService::class)->rejectRapor($record, $data['rejection_note']);

                        Notification::make()
                            ->title('Rapor ditolak')
                            ->body('Rapor dikembalikan ke status Draft dengan catatan penolakan.')
                            ->warning()
                            ->send();
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
