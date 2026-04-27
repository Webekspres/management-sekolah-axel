<?php

namespace App\Filament\Pages;

use App\Models\TemporaryPolicyGrant;
use App\Models\User;
use App\Models\UserPolicyAbility;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Action as TableAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActiveTemporaryAccessList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Akses Aktif';

    protected static ?string $title = 'Daftar Akses Sementara Aktif';

    protected static ?string $slug = 'akses-aktif';

    protected static string|\UnitEnum|null $navigationGroup = 'Manajemen Akses';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.active-temporary-access-list';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->role === 'super_admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                UserPolicyAbility::query()
                    ->with(['user', 'accessPolicy', 'grantedBy', 'level'])
                    ->direct()
                    ->notExpired()
                    ->orderBy('expires_at')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->description(fn (UserPolicyAbility $record): string => $record->user?->email ?? ''),

                TextColumn::make('accessPolicy.name')
                    ->label('Policy')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

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
                    ->badge()
                    ->color('primary'),

                TextColumn::make('expires_at')
                    ->label('Berakhir')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->color(fn (UserPolicyAbility $record): string => $record->expires_at?->diffInHours(now()) < 24
                        ? 'danger'
                        : 'gray'
                    )
                    ->description(fn (UserPolicyAbility $record): string => $record->expires_at
                        ? $record->expires_at->diffForHumans()
                        : ''
                    ),

                TextColumn::make('grantedBy.name')
                    ->label('Diberikan Oleh')
                    ->searchable()
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
            ])
            ->recordActions([
                TableAction::make('revoke')
                    ->label('Cabut')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cabut Akses')
                    ->modalDescription(fn (UserPolicyAbility $record): string => "Cabut ability '{$record->ability}' pada policy '{$record->accessPolicy?->name}' untuk {$record->user?->name}?")
                    ->action(fn (UserPolicyAbility $record) => $this->revokeAbility($record)),
            ])
            ->emptyStateIcon(Heroicon::OutlinedShieldCheck)
            ->emptyStateHeading('Tidak ada akses aktif')
            ->emptyStateDescription('Belum ada akses sementara yang sedang aktif saat ini.')
            ->defaultSort('expires_at', 'asc')
            ->striped();
    }

    private function revokeAbility(UserPolicyAbility $ability): void
    {
        $policyName = $ability->accessPolicy?->name;
        $userName = $ability->user?->name;
        $abilityName = $ability->ability;
        $userId = $ability->user_id;
        $policyId = $ability->access_policy_id;

        $ability->delete();

        $remaining = UserPolicyAbility::query()
            ->forUser($userId)
            ->forPolicy($policyId)
            ->direct()
            ->notExpired()
            ->count();

        if ($remaining === 0) {
            TemporaryPolicyGrant::query()
                ->where('user_id', $userId)
                ->where('access_policy_id', $policyId)
                ->delete();
        }

        Notification::make()
            ->title('Akses dicabut')
            ->body("Ability '{$abilityName}' pada policy '{$policyName}' untuk {$userName} telah dicabut.")
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assign')
                ->label('Berikan Akses Baru')
                ->icon(Heroicon::OutlinedPlus)
                ->url(TemporaryAccessManagement::getUrl()),
        ];
    }
}
