<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\TemporaryAccessLog;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TemporaryAccessLogList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Log Akses';

    protected static ?string $title = 'Log Akses Sementara';

    protected static ?string $slug = 'log-akses';

    protected static string|\UnitEnum|null $navigationGroup = 'Manajemen Akses';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.temporary-access-log-list';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->hasUserRole(UserRole::SuperAdmin) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TemporaryAccessLog::query()
                    ->with(['user', 'accessPolicy', 'level', 'grantedBy', 'revokedBy'])
                    ->latest('granted_at')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->description(fn (TemporaryAccessLog $record): string => $record->user?->email ?? ''),

                TextColumn::make('accessPolicy.name')
                    ->label('Policy')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ability')
                    ->label('Ability')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'viewAny' => 'info',
                        'view' => 'gray',
                        'create' => 'success',
                        'update' => 'warning',
                        'delete' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'viewAny' => 'Lihat Semua',
                        'view' => 'Lihat Detail',
                        'create' => 'Buat',
                        'update' => 'Edit',
                        'delete' => 'Hapus',
                        default => ucfirst($state),
                    }),

                TextColumn::make('level.name')
                    ->label('Jenjang')
                    ->placeholder('Semua jenjang')
                    ->badge(),

                TextColumn::make('granted_at')
                    ->label('Diberikan Pada')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('grantedBy.name')
                    ->label('Diberikan Oleh')
                    ->searchable(),

                TextColumn::make('expires_at')
                    ->label('Berakhir')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('revoked_at')
                    ->label('Dicabut Pada')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('revokedBy.name')
                    ->label('Dicabut Oleh')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('access_policy_id')
                    ->label('Policy')
                    ->relationship('accessPolicy', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('ability')
                    ->label('Ability')
                    ->options([
                        'viewAny' => 'Lihat Semua',
                        'view' => 'Lihat Detail',
                        'create' => 'Buat',
                        'update' => 'Edit',
                        'delete' => 'Hapus',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'revoked' => 'Dicabut',
                        'expired' => 'Kedaluwarsa',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'active' => $query->active(),
                        'revoked' => $query->revoked(),
                        'expired' => $query->expired(),
                        default => $query,
                    }),
            ])
            ->defaultSort('granted_at', 'desc')
            ->striped();
    }
}
