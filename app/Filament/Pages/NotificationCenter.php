<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Action as TableAction;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class NotificationCenter extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Notifikasi';

    protected static ?string $title = 'Pusat Notifikasi';

    protected static ?string $slug = 'notifications';

    protected static string|\UnitEnum|null $navigationGroup = 'Informasi Terkini';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.notification-center';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DatabaseNotification::query()
                    ->where('notifiable_id', auth()->id())
                    ->where('notifiable_type', auth()->user()?->getMorphClass() ?? 'App\Models\User')
                    ->latest()
            )
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->icon(fn (?string $state): string => $state === null
                        ? Heroicon::SolidBell
                        : Heroicon::OutlinedBell
                    )
                    ->color(fn (?string $state): string => $state === null ? 'warning' : 'gray')
                    ->size('lg'),

                TextColumn::make('data')
                    ->label('Judul')
                    ->formatStateUsing(function (DatabaseNotification $record): string {
                        $data = $record->data;

                        return is_array($data)
                            ? ($data['title'] ?? $data[0]['title'] ?? 'Notifikasi')
                            : 'Notifikasi';
                    })
                    ->description(function (DatabaseNotification $record): string {
                        $data = $record->data;
                        $body = is_array($data) ? ($data['body'] ?? $data[0]['body'] ?? '') : '';

                        return $body;
                    })
                    ->searchable()
                    ->wrap(),

                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Diterima')
                    ->dateTime('d M Y H:i')
                    ->description(fn (DatabaseNotification $record): string => $record->created_at?->diffForHumans() ?? '')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('unread')
                    ->label('Belum dibaca')
                    ->query(fn (Builder $query) => $query->whereNull('read_at'))
                    ->default(),

                Filter::make('read')
                    ->label('Sudah dibaca')
                    ->query(fn (Builder $query) => $query->whereNotNull('read_at')),

                Filter::make('created_at')
                    ->label('Tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->recordActions([
                TableAction::make('markAsRead')
                    ->label('Tandai dibaca')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->visible(fn (DatabaseNotification $record): bool => $record->read_at === null)
                    ->action(function (DatabaseNotification $record): void {
                        $record->update(['read_at' => now()]);

                        Notification::make()
                            ->title('Notifikasi ditandai dibaca')
                            ->success()
                            ->send();
                    }),

                TableAction::make('markAsUnread')
                    ->label('Tandai belum dibaca')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->visible(fn (DatabaseNotification $record): bool => $record->read_at !== null)
                    ->action(function (DatabaseNotification $record): void {
                        $record->update(['read_at' => null]);

                        Notification::make()
                            ->title('Notifikasi dikembalikan ke belum dibaca')
                            ->success()
                            ->send();
                    }),

                TableAction::make('delete')
                    ->label('Hapus')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (DatabaseNotification $record): void {
                        $record->delete();

                        Notification::make()
                            ->title('Notifikasi dihapus')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('markAsRead')
                    ->label('Tandai dibaca')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->action(function (Collection $records): void {
                        DatabaseNotification::query()
                            ->whereIn('id', $records->pluck('id'))
                            ->whereNull('read_at')
                            ->update(['read_at' => now()]);

                        Notification::make()
                            ->title(count($records).' notifikasi ditandai dibaca')
                            ->success()
                            ->send();
                    }),

                BulkAction::make('delete')
                    ->label('Hapus')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        DatabaseNotification::query()
                            ->whereIn('id', $records->pluck('id'))
                            ->delete();

                        Notification::make()
                            ->title(count($records).' notifikasi dihapus')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateIcon(Heroicon::OutlinedBell)
            ->emptyStateHeading('Belum ada notifikasi')
            ->emptyStateDescription('Notifikasi akan muncul di sini saat ada aktivitas yang membutuhkan perhatian Anda.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAllAsRead')
                ->label('Tandai semua dibaca')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => DatabaseNotification::query()
                    ->where('notifiable_id', auth()->id())
                    ->where('notifiable_type', auth()->user()?->getMorphClass() ?? 'App\Models\User')
                    ->whereNull('read_at')
                    ->exists()
                )
                ->action(function (): void {
                    DatabaseNotification::query()
                        ->where('notifiable_id', auth()->id())
                        ->where('notifiable_type', auth()->user()?->getMorphClass() ?? 'App\Models\User')
                        ->whereNull('read_at')
                        ->update(['read_at' => now()]);

                    Notification::make()
                        ->title('Semua notifikasi ditandai dibaca')
                        ->success()
                        ->send();

                    $this->refreshTable();
                }),
        ];
    }

    /**
     * Refresh the table after header action.
     */
    public function refreshTable(): void
    {
        $this->resetTable();
    }
}
