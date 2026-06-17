<?php

use App\Filament\Pages\ActiveTemporaryAccessList;
use App\Models\AccessPolicy;
use App\Models\Level;
use App\Models\TemporaryAccessLog;
use App\Models\User;
use App\Models\UserPolicyAbility;
use App\Support\TemporaryAccessManager;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

// ---------------------------------------------------------------------------
// Setup
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->admin = User::factory()->asAdmin()->create();
    actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

// ===========================================================================
// Requirement 3.2 — Log dibuat saat akses diberikan
// ===========================================================================

test('assignAbility membuat TemporaryAccessLog dengan data yang benar', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = AccessPolicy::query()->where('code', 'lesson_plan_management')->firstOrFail();
    $admin = User::factory()->asAdmin()->create();
    $expiresAt = now()->addWeek();

    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, $expiresAt);

    assertDatabaseHas(TemporaryAccessLog::class, [
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'ability' => 'viewAny',
        'level_id' => null,
        'granted_by_user_id' => $admin->id,
        'revoked_at' => null,
        'revoked_by_user_id' => null,
    ]);

    $log = TemporaryAccessLog::query()
        ->where('user_id', $user->id)
        ->where('access_policy_id', $policy->id)
        ->where('ability', 'viewAny')
        ->first();

    expect($log)->not->toBeNull();
    expect(Carbon::parse($log->expires_at)->toDateString())->toBe($expiresAt->toDateString());
    expect($log->granted_at)->not->toBeNull();
});

test('assignAbility membuat TemporaryAccessLog dengan level_id yang benar saat akses dibatasi per jenjang', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = AccessPolicy::query()->where('code', 'lesson_plan_management')->firstOrFail();
    $admin = User::factory()->asAdmin()->create();
    $level = Level::factory()->create();
    $expiresAt = now()->addWeek();

    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, $expiresAt, $level->id);

    assertDatabaseHas(TemporaryAccessLog::class, [
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'ability' => 'viewAny',
        'level_id' => $level->id,
        'granted_by_user_id' => $admin->id,
    ]);
});

test('assignAbility membuat log terpisah untuk setiap ability yang diberikan', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = AccessPolicy::query()->where('code', 'lesson_plan_management')->firstOrFail();
    $admin = User::factory()->asAdmin()->create();
    $expiresAt = now()->addWeek();

    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, $expiresAt);
    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'create', $admin, $expiresAt);

    expect(TemporaryAccessLog::query()
        ->where('user_id', $user->id)
        ->where('access_policy_id', $policy->id)
        ->count()
    )->toBe(2);
});

test('assignAbility tidak membuat log saat ability sudah diwarisi dari role', function () {
    $guru = User::factory()->asGuru()->create();
    $policy = AccessPolicy::query()->where('code', 'lesson_plan_management')->firstOrFail();
    $admin = User::factory()->asAdmin()->create();

    app(TemporaryAccessManager::class)->assignAbility($guru, $policy, 'viewAny', $admin, now()->addWeek());

    expect(TemporaryAccessLog::query()
        ->where('user_id', $guru->id)
        ->where('access_policy_id', $policy->id)
        ->count()
    )->toBe(0);
});

// ===========================================================================
// Requirement 3.3 — Log diperbarui saat akses dicabut
// ===========================================================================

test('cabut akses mengisi revoked_at dan revoked_by_user_id pada log yang sesuai', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = AccessPolicy::query()->where('code', 'lesson_plan_management')->firstOrFail();
    $admin = User::factory()->asAdmin()->create();

    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, now()->addWeek());

    $ability = UserPolicyAbility::query()
        ->forUser($user->id)
        ->forPolicy($policy->id)
        ->forAbility('viewAny')
        ->direct()
        ->firstOrFail();

    Livewire::test(ActiveTemporaryAccessList::class)
        ->callAction(TestAction::make('revoke')->table($ability));

    $log = TemporaryAccessLog::query()
        ->where('user_id', $user->id)
        ->where('access_policy_id', $policy->id)
        ->where('ability', 'viewAny')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->revoked_at)->not->toBeNull();
    expect($log->revoked_by_user_id)->toBe($this->admin->id);
});

test('cabut akses hanya mengupdate log yang revoked_at masih null', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = AccessPolicy::query()->where('code', 'lesson_plan_management')->firstOrFail();
    $admin = User::factory()->asAdmin()->create();

    // Buat log lama yang sudah dicabut sebelumnya (simulasi riwayat)
    TemporaryAccessLog::create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'ability' => 'viewAny',
        'granted_by_user_id' => $admin->id,
        'granted_at' => now()->subWeek(),
        'expires_at' => now()->subDay(),
        'revoked_at' => now()->subDay(),
        'revoked_by_user_id' => $admin->id,
    ]);

    // Buat akses baru
    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, now()->addWeek());

    $ability = UserPolicyAbility::query()
        ->forUser($user->id)
        ->forPolicy($policy->id)
        ->forAbility('viewAny')
        ->direct()
        ->firstOrFail();

    Livewire::test(ActiveTemporaryAccessList::class)
        ->callAction(TestAction::make('revoke')->table($ability));

    // Hanya 1 log yang baru dicabut (yang revoked_at-nya baru diisi)
    $recentlyRevoked = TemporaryAccessLog::query()
        ->where('user_id', $user->id)
        ->where('access_policy_id', $policy->id)
        ->where('ability', 'viewAny')
        ->whereNotNull('revoked_at')
        ->count();

    expect($recentlyRevoked)->toBe(2); // log lama + log baru yang baru dicabut
});
