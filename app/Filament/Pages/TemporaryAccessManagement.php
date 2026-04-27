<?php

namespace App\Filament\Pages;

use App\Models\AccessPolicy;
use App\Models\Level;
use App\Models\TemporaryPolicyGrant;
use App\Models\User;
use App\Models\UserPolicyAbility;
use App\Support\TemporaryAccessManager;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TemporaryAccessManagement extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Akses Sementara';

    protected static ?string $title = 'Akses Sementara';

    protected static ?string $slug = 'akses-sementara';

    protected static string|\UnitEnum|null $navigationGroup = 'Manajemen Akses';

    protected string $view = 'filament.pages.temporary-access-management';

    /**
     * @var array{
     *     user_ids: array<int, string>,
     *     policy_abilities: array<string, array<int, string>>,
     *     policy_levels: array<string, array<int, string>>,
     *     duration: string,
     *     custom_expires_at: string|null
     * }
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->role === 'super_admin';
    }

    public function mount(): void
    {
        $this->resetForm();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Section::make('Pemberian Akses')
                    ->description('Pilih user dan assign abilities granular untuk setiap policy. Abilities yang inherited dari role ditampilkan disabled.')
                    ->schema([
                        Select::make('user_ids')
                            ->label('Pilih User')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->options($this->getUserOptions()),
                        $this->buildPolicyAbilitiesSection(),
                        Select::make('duration')
                            ->label('Durasi Akses')
                            ->required()
                            ->live()
                            ->options([
                                '1_day' => '1 Hari',
                                '1_week' => '1 Minggu',
                                '1_month' => '1 Bulan',
                                '1_year' => '1 Tahun',
                                'custom' => 'Custom Date',
                            ]),
                        DateTimePicker::make('custom_expires_at')
                            ->label('Tanggal Kedaluwarsa Custom')
                            ->seconds(false)
                            ->native(false)
                            ->required(fn (callable $get): bool => $get('duration') === 'custom')
                            ->visible(fn (callable $get): bool => $get('duration') === 'custom')
                            ->minDate(now()->addMinute()),
                    ]),
            ]);
    }

    private function buildPolicyAbilitiesSection(): Section
    {
        return Section::make('Policies & Abilities')
            ->description('Centang abilities yang ingin diberikan. Pilih jenjang untuk membatasi akses per jenjang (kosongkan = semua jenjang). Abilities yang di-disable adalah inherited dari role.')
            ->schema(fn (Get $get): array => $this->getPolicyAbilitiesSchema($get('user_ids') ?? []))
            ->collapsible();
    }

    /**
     * @param  array<int, string>  $selectedUserIds
     * @return array<int, mixed>
     */
    private function getPolicyAbilitiesSchema(array $selectedUserIds = []): array
    {
        if (empty($selectedUserIds)) {
            return [];
        }

        $policies = AccessPolicy::query()->active()->orderBy('name')->get();
        $levelOptions = $this->getLevelOptions();
        $schema = [];

        foreach ($policies as $policy) {
            $abilities = $policy->getAllAbilities();

            if (empty($abilities)) {
                continue;
            }

            $schema[] = Section::make($policy->name)
                ->description($policy->description)
                ->schema([
                    CheckboxList::make("policy_abilities.{$policy->id}")
                        ->label('Abilities')
                        ->hiddenLabel()
                        ->options($this->buildAbilityOptions($selectedUserIds, $policy, $abilities))
                        ->disableOptionWhen(fn (string $value): bool => $this->isAbilityInherited($selectedUserIds, $policy->id, $value))
                        ->live()
                        ->columns(3),
                    Select::make("policy_levels.{$policy->id}")
                        ->label('Batasi ke Jenjang')
                        ->multiple()
                        ->options($levelOptions)
                        ->placeholder('Semua jenjang')
                        ->helperText('Kosongkan untuk akses ke semua jenjang.')
                        ->visible(fn (Get $get): bool => ! empty($get("policy_abilities.{$policy->id}"))),
                ])
                ->collapsible()
                ->compact();
        }

        return $schema;
    }

    /**
     * @param  array<int, string>  $userIds
     * @param  array<int, string>  $abilities
     * @return array<string, string>
     */
    private function buildAbilityOptions(array $userIds, AccessPolicy $policy, array $abilities): array
    {
        $abilityLabels = [
            'viewAny' => 'Lihat Semua',
            'view' => 'Lihat Detail',
            'create' => 'Buat',
            'update' => 'Edit',
            'delete' => 'Hapus',
        ];

        $options = [];
        foreach ($abilities as $ability) {
            $label = $abilityLabels[$ability] ?? ucfirst($ability);
            $options[$ability] = $label;
        }

        return $options;
    }

    private function isAbilityInherited(array $userIds, string $policyId, string $ability): bool
    {
        if (empty($userIds)) {
            return false;
        }

        $policy = AccessPolicy::find($policyId);

        if (! $policy) {
            return false;
        }

        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            if ($policy->isAbilityInherited($user, $ability)) {
                return true;
            }
        }

        return false;
    }

    public function submit(): void
    {
        $this->validate([
            'data.user_ids' => ['required', 'array', 'min:1'],
            'data.duration' => ['required', 'string'],
            'data.custom_expires_at' => [
                'nullable',
                'date',
                'required_if:data.duration,custom',
                'after:now',
            ],
        ]);

        $policyAbilities = $this->data['policy_abilities'] ?? [];
        $policyLevels = $this->data['policy_levels'] ?? [];

        $hasAbilities = false;
        foreach ($policyAbilities as $abilities) {
            if (! empty($abilities)) {
                $hasAbilities = true;
                break;
            }
        }

        if (! $hasAbilities) {
            Notification::make()
                ->title('Validasi Gagal')
                ->body('Silakan pilih minimal satu ability.')
                ->danger()
                ->send();

            return;
        }

        /** @var User $grantedBy */
        $grantedBy = auth()->user();
        $temporaryAccessManager = app(TemporaryAccessManager::class);
        $expiresAt = $this->resolveExpiresAt();

        $users = User::query()->whereIn('id', $this->data['user_ids'])->get();

        $createdAbilityCount = 0;
        $inheritedAbilityCount = 0;
        $errors = [];

        foreach ($users as $user) {
            foreach ($policyAbilities as $policyId => $abilities) {
                $policy = AccessPolicy::query()->active()->find($policyId);

                if (! $policy) {
                    continue;
                }

                $selectedLevelIds = $policyLevels[$policyId] ?? [];
                $levelIdsToAssign = empty($selectedLevelIds) ? [null] : $selectedLevelIds;

                foreach ($abilities as $ability) {
                    if ($policy->isAbilityInherited($user, $ability)) {
                        $inheritedAbilityCount++;

                        continue;
                    }

                    foreach ($levelIdsToAssign as $levelId) {
                        try {
                            if ($temporaryAccessManager->assignAbility($user, $policy, $ability, $grantedBy, $expiresAt, $levelId)) {
                                $createdAbilityCount++;
                            }
                        } catch (\Exception $e) {
                            $errors[] = "{$user->name} - {$policy->name}:{$ability} - {$e->getMessage()}";
                        }
                    }
                }
            }
        }

        if (! empty($errors)) {
            Notification::make()
                ->title('Beberapa akses mengalami error')
                ->body(implode(PHP_EOL, array_slice($errors, 0, 5)))
                ->warning()
                ->send();
        }

        if ($createdAbilityCount === 0) {
            Notification::make()
                ->title('Tidak ada perubahan tersimpan')
                ->body('Data tidak berubah atau gagal tersimpan. Silakan cek notifikasi warning/error di atas.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Akses berhasil disimpan')
            ->body("Akses disimpan: {$createdAbilityCount} abilities baru, {$inheritedAbilityCount} abilities inherited (tidak ditambah).")
            ->success()
            ->send();

        $this->resetForm();
    }

    /**
     * Revoke a single UserPolicyAbility by ID.
     */
    public function revokeAbility(string $abilityId): void
    {
        $ability = UserPolicyAbility::find($abilityId);

        if (! $ability) {
            return;
        }

        $policy = $ability->accessPolicy;
        $user = $ability->user;

        $ability->delete();

        // Clean up TemporaryPolicyGrant if no more active abilities remain for this policy
        $remaining = UserPolicyAbility::query()
            ->forUser($user->id)
            ->forPolicy($ability->access_policy_id)
            ->direct()
            ->notExpired()
            ->count();

        if ($remaining === 0) {
            TemporaryPolicyGrant::query()
                ->where('user_id', $user->id)
                ->where('access_policy_id', $ability->access_policy_id)
                ->delete();
        }

        Notification::make()
            ->title('Akses dicabut')
            ->body("Ability '{$ability->ability}' pada policy '{$policy?->name}' untuk {$user?->name} telah dicabut.")
            ->success()
            ->send();
    }

    /**
     * Get all currently active (not-expired) direct UserPolicyAbilities.
     *
     * @return Collection<int, UserPolicyAbility>
     */
    public function getActiveAbilities(): Collection
    {
        return UserPolicyAbility::query()
            ->with(['user', 'accessPolicy', 'grantedBy', 'level'])
            ->direct()
            ->notExpired()
            ->orderBy('expires_at')
            ->get();
    }

    private function resetForm(): void
    {
        $policyAbilities = [];
        $policyLevels = [];
        $policies = AccessPolicy::query()->active()->get();

        foreach ($policies as $policy) {
            $policyAbilities[$policy->id] = [];
            $policyLevels[$policy->id] = [];
        }

        $this->form->fill([
            'user_ids' => [],
            'policy_abilities' => $policyAbilities,
            'policy_levels' => $policyLevels,
            'duration' => '1_week',
            'custom_expires_at' => null,
        ]);
    }

    public function isSaveDisabled(): bool
    {
        $data = $this->data ?? [];

        if (blank($data['user_ids'] ?? [])) {
            return true;
        }

        if (blank($data['duration'] ?? null)) {
            return true;
        }

        return false;
    }

    /**
     * Only active users can receive temporary access.
     *
     * @return array<string, string>
     */
    public function getUserOptions(): array
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => "{$user->name} ({$user->email})"])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function getLevelOptions(): array
    {
        return Level::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Level $level): array => [$level->id => $level->name])
            ->all();
    }

    private function resolveExpiresAt(): CarbonInterface
    {
        return match ($this->data['duration']) {
            '1_day' => now()->addDay(),
            '1_week' => now()->addWeek(),
            '1_month' => now()->addMonth(),
            '1_year' => now()->addYear(),
            'custom' => Carbon::parse($this->data['custom_expires_at']),
            default => now()->addWeek(),
        };
    }
}
