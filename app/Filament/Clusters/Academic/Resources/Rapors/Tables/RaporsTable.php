<?php

namespace App\Filament\Clusters\Academic\Resources\Rapors\Tables;

use App\Models\Rapor;
use App\Services\RaporService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RaporsTable
{
    public static function configure(Table $table): Table
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
                TextColumn::make('approved_at')
                    ->label('Disetujui Pada')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('rejection_note')
                    ->label('Catatan Penolakan')
                    ->limit(50)
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
                    ->action(function (Rapor $record): void {
                        app(RaporService::class)->approveRapor($record);

                        Notification::make()
                            ->title('Rapor berhasil disetujui')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Tolak / Kembalikan')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('danger')
                    ->visible(fn (Rapor $record): bool => $record->isFinalized())
                    ->schema([
                        Textarea::make('rejection_note')
                            ->label('Catatan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Rapor $record, array $data): void {
                        app(RaporService::class)->rejectRapor($record, $data['rejection_note']);

                        Notification::make()
                            ->title('Rapor dikembalikan ke Draft')
                            ->warning()
                            ->send();
                    }),

                Action::make('change_status')
                    ->label('Ubah Status')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('gray')
                    ->schema([
                        Select::make('status')
                            ->label('Status Baru')
                            ->options([
                                'DRAFT' => 'Draft',
                                'FINALIZED' => 'Terfinalisasi',
                                'APPROVED' => 'Disetujui',
                            ])
                            ->required(),
                    ])
                    ->action(function (Rapor $record, array $data): void {
                        $record->update(['status' => $data['status']]);

                        Notification::make()
                            ->title('Status rapor diperbarui')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
