<?php

namespace App\Filament\Pages;

use App\Models\AccessPolicy;
use App\Models\TemporaryPolicyGrant;
use App\Models\TemporaryRoleElevation;
use App\Models\User;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

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
     *     policy_ids: array<int, string>,
     *     temporary_role: string|null,
     *     duration: string,
     *     custom_expires_at: string|null
     * }
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->effectiveRole() === 'super_admin';
    }

    public function mount(): void
    {
        $this->form->fill([
            'user_ids' => [],
            'policy_ids' => [],
            'temporary_role' => null,
            'duration' => '1_week',
            'custom_expires_at' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Section::make('Pemberian Akses')
                    ->description('Pilih user, policy, dan durasi akses sementara. Opsi role level bersifat tambahan.')
                    ->schema([
                        Select::make('user_ids')
                            ->label('Pilih User')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options($this->getUserOptions()),
                        CheckboxList::make('policy_ids')
                            ->label('Pilih Policies')
                            ->required()
                            ->options($this->getPolicyOptions())
                            ->descriptions($this->getPolicyDescriptions())
                            ->columns(1),
                        Select::make('temporary_role')
                            ->label('Temporary Role Elevation (Opsional)')
                            ->options([
                                'guru' => 'Guru',
                                'kepala_sekolah' => 'Kepala Sekolah',
                                'super_admin' => 'Super Admin',
                            ])
                            ->helperText('Jika dipilih, user akan mewarisi hak role ini sampai waktu berakhir.'),
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

    public function submit(): void
    {
        $this->validate([
            'data.user_ids' => ['required', 'array', 'min:1'],
            'data.policy_ids' => ['required', 'array', 'min:1'],
            'data.duration' => ['required', 'string'],
            'data.custom_expires_at' => ['nullable', 'date', 'required_if:data.duration,custom'],
            'data.temporary_role' => ['nullable', 'in:guru,kepala_sekolah,super_admin'],
        ]);

        $expiresAt = $this->resolveExpiresAt();
        $grantedByUserId = auth()->id();
        $temporaryAccessManager = app(TemporaryAccessManager::class);

        $users = User::query()->whereIn('id', $this->data['user_ids'])->get();
        $policies = AccessPolicy::query()
            ->active()
            ->whereIn('id', $this->data['policy_ids'])
            ->get()
            ->keyBy('id');

        $createdPolicyGrantCount = 0;
        $createdRoleElevationCount = 0;
        $skippedPermanent = [];

        foreach ($users as $user) {
            foreach ($policies as $policy) {
                if ($temporaryAccessManager->isPermanentlyAllowedByRole($user, $policy)) {
                    $skippedPermanent[] = "{$user->name} - {$policy->name}";

                    continue;
                }

                $alreadyGranted = TemporaryPolicyGrant::query()
                    ->where('user_id', $user->id)
                    ->where('access_policy_id', $policy->id)
                    ->where('expires_at', '>', now())
                    ->exists();

                if ($alreadyGranted) {
                    continue;
                }

                TemporaryPolicyGrant::query()->create([
                    'user_id' => $user->id,
                    'access_policy_id' => $policy->id,
                    'granted_by_user_id' => $grantedByUserId,
                    'expires_at' => $expiresAt,
                ]);

                $createdPolicyGrantCount++;
            }

            if (filled($this->data['temporary_role'])) {
                TemporaryRoleElevation::query()->create([
                    'user_id' => $user->id,
                    'elevated_role' => $this->data['temporary_role'],
                    'granted_by_user_id' => $grantedByUserId,
                    'expires_at' => $expiresAt,
                ]);

                $createdRoleElevationCount++;
            }
        }

        if (! empty($skippedPermanent)) {
            Notification::make()
                ->title('Sebagian policy dilewati karena sudah menjadi akses permanen.')
                ->body(implode(PHP_EOL, array_slice($skippedPermanent, 0, 5)))
                ->warning()
                ->send();
        }

        Notification::make()
            ->title('Akses sementara berhasil disimpan.')
            ->body("Grant policy: {$createdPolicyGrantCount}, elevasi role: {$createdRoleElevationCount}")
            ->success()
            ->send();

        $this->form->fill([
            'user_ids' => [],
            'policy_ids' => [],
            'temporary_role' => null,
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

        if (blank($data['policy_ids'] ?? [])) {
            return true;
        }

        if (blank($data['duration'] ?? null)) {
            return true;
        }

        if (($data['duration'] ?? null) === 'custom' && blank($data['custom_expires_at'] ?? null)) {
            return true;
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function getUserOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => "{$user->name} ({$user->email})"])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function getPolicyOptions(): array
    {
        return AccessPolicy::query()
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function getPolicyDescriptions(): array
    {
        return AccessPolicy::query()
            ->active()
            ->orderBy('name')
            ->pluck('description', 'id')
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
