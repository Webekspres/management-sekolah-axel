<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Activity Log';

    protected static ?string $title = 'Activity Log';

    protected static ?string $slug = 'activity-log';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.activity-log-page';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->can('viewAny', ActivityLog::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ActivityLog::query()
                    ->with('user')
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->description(fn (ActivityLog $record): string => $record->user?->email ?? 'System'),

                TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'login', 'logout' => 'info',
                        'downloaded', 'generated' => 'primary',
                        'approved' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('log_name')
                    ->label('Modul')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('entity_type')
                    ->label('Entity')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->description(fn (ActivityLog $record): string => $record->entity_id ?? ''),

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->wrap()
                    ->limit(80),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->description(fn (ActivityLog $record): string => $record->created_at?->diffForHumans() ?? '')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('action')
                    ->label('Aksi')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'downloaded' => 'Downloaded',
                        'generated' => 'Generated',
                        'approved' => 'Approved',
                    ]),

                SelectFilter::make('log_name')
                    ->label('Modul')
                    ->options([
                        'auth' => 'Auth',
                        'spp' => 'SPP',
                        'absensi' => 'Absensi',
                        'rpp' => 'RPP',
                        'kbm' => 'KBM',
                        'rapor' => 'Rapor',
                        'jadwal' => 'Jadwal',
                        'user' => 'User',
                        'siswa' => 'Siswa',
                        'guru' => 'Guru',
                        'general' => 'General',
                    ]),

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
            ->emptyStateIcon(Heroicon::OutlinedClipboardDocumentList)
            ->emptyStateHeading('Belum ada aktivitas')
            ->emptyStateDescription('Aktivitas pengguna akan muncul di sini setelah ada interaksi dengan sistem.');
    }
}
