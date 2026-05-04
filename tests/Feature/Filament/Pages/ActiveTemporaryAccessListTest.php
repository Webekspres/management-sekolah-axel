<?php

use App\Filament\Pages\ActiveTemporaryAccessList;
use App\Models\AccessPolicy;
use App\Models\TemporaryPolicyGrant;
use App\Models\User;
use App\Models\UserPolicyAbility;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;

// ---------------------------------------------------------------------------
// Setup
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->admin = User::factory()->asAdmin()->create();
    actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

// ===========================================================================
// Requirement 2.3 — Bulk action cabut akses
// ===========================================================================

test('bulk action menghapus semua UserPolicyAbility yang dipilih dari database', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = AccessPolicy::factory()->create();

    $abilities = collect(['viewAny', 'create', 'update'])->map(fn (string $ability) => UserPolicyAbility::factory()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'ability' => $ability,
        'is_inherited' => false,
        'expires_at' => now()->addWeek(),
    ]));

    Livewire::test(ActiveTemporaryAccessList::class)
        ->selectTableRecords($abilities->pluck('id')->toArray())
        ->callAction(TestAction::make('revokeSelected')->table()->bulk())
        ->assertNotified();

    foreach ($abilities as $ability) {
        assertDatabaseMissing(UserPolicyAbility::class, ['id' => $ability->id]);
    }
});

test('bulk revoke semua abilities untuk satu user dan policy juga menghapus TemporaryPolicyGrant', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = AccessPolicy::factory()->create();

    // Buat TemporaryPolicyGrant untuk user+policy ini
    $grant = TemporaryPolicyGrant::create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'granted_by_user_id' => $this->admin->id,
        'expires_at' => now()->addWeek(),
    ]);

    // Buat beberapa abilities untuk user+policy yang sama
    $abilities = collect(['viewAny', 'create'])->map(fn (string $ability) => UserPolicyAbility::factory()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'ability' => $ability,
        'is_inherited' => false,
        'expires_at' => now()->addWeek(),
    ]));

    Livewire::test(ActiveTemporaryAccessList::class)
        ->selectTableRecords($abilities->pluck('id')->toArray())
        ->callAction(TestAction::make('revokeSelected')->table()->bulk())
        ->assertNotified();

    // Semua abilities harus terhapus
    foreach ($abilities as $ability) {
        assertDatabaseMissing(UserPolicyAbility::class, ['id' => $ability->id]);
    }

    // TemporaryPolicyGrant juga harus terhapus karena tidak ada ability aktif tersisa
    assertDatabaseMissing(TemporaryPolicyGrant::class, [
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
    ]);
});

test('success notification muncul setelah bulk action selesai', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = AccessPolicy::factory()->create();

    $abilities = collect(['viewAny', 'create', 'update'])->map(fn (string $ability) => UserPolicyAbility::factory()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'ability' => $ability,
        'is_inherited' => false,
        'expires_at' => now()->addWeek(),
    ]));

    Livewire::test(ActiveTemporaryAccessList::class)
        ->selectTableRecords($abilities->pluck('id')->toArray())
        ->callAction(TestAction::make('revokeSelected')->table()->bulk())
        ->assertNotified('3 akses berhasil dicabut');
});
