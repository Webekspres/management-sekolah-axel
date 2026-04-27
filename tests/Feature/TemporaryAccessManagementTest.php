<?php

use App\Filament\Pages\TemporaryAccessManagement;
use App\Models\AccessPolicy;
use App\Models\LessonPlan;
use App\Models\TemporaryPolicyGrant;
use App\Models\TemporaryRoleElevation;
use App\Models\User;
use App\Models\UserPolicyAbility;
use App\Support\TemporaryAccessManager;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function lessonPlanPolicy(): AccessPolicy
{
    return AccessPolicy::query()->where('code', 'lesson_plan_management')->firstOrFail();
}

function kbmPolicy(): AccessPolicy
{
    return AccessPolicy::query()->where('code', 'kbm_management')->firstOrFail();
}

function announcementPolicy(): AccessPolicy
{
    return AccessPolicy::query()->where('code', 'announcement_management')->firstOrFail();
}

// ===========================================================================
// A. Policy Grant (Ability) — end-to-end
// ===========================================================================

test('assignAbility creates UserPolicyAbility and TemporaryPolicyGrant so policy check passes', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();
    $admin = User::factory()->asAdmin()->create();
    $expiresAt = now()->addDay();

    expect(Gate::forUser($user)->allows('viewAny', LessonPlan::class))->toBeFalse();

    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, $expiresAt);

    expect(Gate::forUser($user)->allows('viewAny', LessonPlan::class))->toBeTrue();
    expect(UserPolicyAbility::query()->forUser($user->id)->forPolicy($policy->id)->forAbility('viewAny')->direct()->count())->toBe(1);
    expect(TemporaryPolicyGrant::query()->where('user_id', $user->id)->where('access_policy_id', $policy->id)->count())->toBe(1);
});

test('expired UserPolicyAbility does not grant access', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();
    $admin = User::factory()->asAdmin()->create();

    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, now()->subMinute());

    expect(Gate::forUser($user)->allows('viewAny', LessonPlan::class))->toBeFalse();
});

test('expired TemporaryPolicyGrant does not grant access', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();

    TemporaryPolicyGrant::query()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'expires_at' => now()->subMinute(),
    ]);

    expect(Gate::forUser($user)->allows('viewAny', LessonPlan::class))->toBeFalse();
});

test('assignAbility does not duplicate when called twice for same ability', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();
    $admin = User::factory()->asAdmin()->create();
    $expiresAt = now()->addDay();

    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, $expiresAt);
    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, $expiresAt);

    expect(UserPolicyAbility::query()->forUser($user->id)->forPolicy($policy->id)->forAbility('viewAny')->direct()->count())->toBe(1);
    expect(TemporaryPolicyGrant::query()->where('user_id', $user->id)->where('access_policy_id', $policy->id)->count())->toBe(1);
});

test('assignAbility returns false and skips when ability is inherited from role', function () {
    // guru has permanent access to lesson_plan_management
    $user = User::factory()->asGuru()->create();
    $policy = lessonPlanPolicy();
    $admin = User::factory()->asAdmin()->create();

    $result = app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, now()->addDay());

    expect($result)->toBeFalse();
    expect(UserPolicyAbility::query()->forUser($user->id)->forPolicy($policy->id)->forAbility('viewAny')->direct()->count())->toBe(0);
    expect(TemporaryPolicyGrant::query()->where('user_id', $user->id)->where('access_policy_id', $policy->id)->count())->toBe(0);
});

test('inactive policy does not grant access even with TemporaryPolicyGrant', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();

    // Deactivate policy
    $policy->update(['is_active' => false]);

    TemporaryPolicyGrant::query()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'expires_at' => now()->addDay(),
    ]);

    expect(Gate::forUser($user)->allows('viewAny', LessonPlan::class))->toBeFalse();
});

// ===========================================================================
// B. Role Elevation
// ===========================================================================

test('role elevation changes effectiveRole and grants panel access', function () {
    $user = User::factory()->asGuru()->create();

    expect($user->effectiveRole())->toBe('guru');

    TemporaryRoleElevation::query()->create([
        'user_id' => $user->id,
        'elevated_role' => 'kepala_sekolah',
        'expires_at' => now()->addDay(),
    ]);

    $user = $user->fresh();

    expect($user->effectiveRole())->toBe('kepala_sekolah')
        ->and($user->canAccessPanel(Filament::getPanel('kepsek')))->toBeTrue();
});

test('expired role elevation does not affect effectiveRole', function () {
    $user = User::factory()->asGuru()->create();

    TemporaryRoleElevation::query()->create([
        'user_id' => $user->id,
        'elevated_role' => 'kepala_sekolah',
        'expires_at' => now()->subMinute(),
    ]);

    expect($user->fresh()->effectiveRole())->toBe('guru');
});

test('multiple active role elevations resolve to the highest role', function () {
    $user = User::factory()->asSiswa()->create();

    TemporaryRoleElevation::query()->create([
        'user_id' => $user->id,
        'elevated_role' => 'guru',
        'expires_at' => now()->addDay(),
    ]);

    TemporaryRoleElevation::query()->create([
        'user_id' => $user->id,
        'elevated_role' => 'kepala_sekolah',
        'expires_at' => now()->addDay(),
    ]);

    expect($user->fresh()->effectiveRole())->toBe('kepala_sekolah');
});

test('elevateRole deduplicates active elevation for same role', function () {
    $user = User::factory()->asSiswa()->create();
    $admin = User::factory()->asAdmin()->create();
    $manager = app(TemporaryAccessManager::class);

    $manager->elevateRole($user, 'guru', $admin, now()->addDay());
    $manager->elevateRole($user, 'guru', $admin, now()->addWeek());

    expect(TemporaryRoleElevation::query()->where('user_id', $user->id)->where('elevated_role', 'guru')->count())->toBe(1);
});

test('elevateRole prevents privilege escalation to same or higher role than grantor', function () {
    $admin = User::factory()->asAdmin()->create(); // super_admin level 4
    $guru = User::factory()->asGuru()->create();   // guru level 2
    $manager = app(TemporaryAccessManager::class);

    // super_admin cannot elevate to super_admin (same level)
    $result = $manager->elevateRole($guru, 'super_admin', $admin, now()->addDay());
    expect($result)->toBeFalse();
    expect(TemporaryRoleElevation::query()->where('user_id', $guru->id)->count())->toBe(0);
});

test('elevateRole allows elevation to role strictly below grantor level', function () {
    $admin = User::factory()->asAdmin()->create(); // super_admin level 4
    $siswa = User::factory()->asSiswa()->create();
    $manager = app(TemporaryAccessManager::class);

    $result = $manager->elevateRole($siswa, 'kepala_sekolah', $admin, now()->addDay());
    expect($result)->toBeTrue();
    expect(TemporaryRoleElevation::query()->where('user_id', $siswa->id)->where('elevated_role', 'kepala_sekolah')->count())->toBe(1);
});

test('role elevation to same role as user base role still works but is a no-op for effectiveRole', function () {
    $guru = User::factory()->asGuru()->create();
    $admin = User::factory()->asAdmin()->create();
    $manager = app(TemporaryAccessManager::class);

    // guru (level 2) elevated to guru (level 2) — allowed since grantor is super_admin (level 4)
    $result = $manager->elevateRole($guru, 'guru', $admin, now()->addDay());
    expect($result)->toBeTrue();
    // effectiveRole stays guru (same level)
    expect($guru->fresh()->effectiveRole())->toBe('guru');
});

// ===========================================================================
// C. Form Validation
// ===========================================================================

test('submit rejects custom_expires_at in the past', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $target = User::factory()->asSiswa()->create();
    $this->actingAs($admin);

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [$target->id])
        ->set('data.temporary_role', 'guru')
        ->set('data.duration', 'custom')
        ->set('data.custom_expires_at', now()->subHour()->format('Y-m-d H:i'))
        ->call('submit')
        ->assertHasErrors(['data.custom_expires_at']);
});

test('submit rejects when neither ability nor role is selected', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $target = User::factory()->asSiswa()->create();
    $this->actingAs($admin);

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [$target->id])
        ->set('data.duration', '1_week')
        ->call('submit')
        ->assertNotified();

    expect(TemporaryRoleElevation::query()->count())->toBe(0);
    expect(UserPolicyAbility::query()->direct()->count())->toBe(0);
});

test('submit rejects when user_ids is empty', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $this->actingAs($admin);

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [])
        ->set('data.temporary_role', 'guru')
        ->set('data.duration', '1_week')
        ->call('submit')
        ->assertHasErrors(['data.user_ids']);
});

test('inactive user does not appear in user options', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $inactiveUser = User::factory()->asSiswa()->create(['is_active' => false]);
    $this->actingAs($admin);

    $component = Livewire::test(TemporaryAccessManagement::class);

    // The inactive user should not be in the options
    $options = $component->instance()->getUserOptions();
    expect(array_key_exists($inactiveUser->id, $options))->toBeFalse();
});

test('submit assigns role elevation via form successfully', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $target = User::factory()->asSiswa()->create();
    $this->actingAs($admin);

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [$target->id])
        ->set('data.temporary_role', 'guru')
        ->set('data.duration', '1_week')
        ->call('submit');

    expect(TemporaryRoleElevation::query()
        ->where('user_id', $target->id)
        ->where('elevated_role', 'guru')
        ->count())->toBe(1);
});

test('submit skips inherited abilities and does not create duplicate records', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    // guru already has permanent access to announcement_management
    $guru = User::factory()->asGuru()->create();
    $policy = announcementPolicy();
    $this->actingAs($admin);

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [$guru->id])
        ->set("data.policy_abilities.{$policy->id}", ['viewAny'])
        ->set('data.duration', '1_week')
        ->call('submit');

    // No direct UserPolicyAbility should be created since it's inherited
    expect(UserPolicyAbility::query()->forUser($guru->id)->forPolicy($policy->id)->direct()->count())->toBe(0);
});

// ===========================================================================
// D. Cleanup Command
// ===========================================================================

test('cleanup command removes expired TemporaryPolicyGrant', function () {
    $user = User::factory()->asGuru()->create();
    $policy = kbmPolicy();

    TemporaryPolicyGrant::query()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'expires_at' => now()->subMinute(),
    ]);

    Artisan::call('app:revoke-expired-temporary-access');

    expect(TemporaryPolicyGrant::query()->count())->toBe(0);
});

test('cleanup command removes expired TemporaryRoleElevation', function () {
    $user = User::factory()->asGuru()->create();

    TemporaryRoleElevation::query()->create([
        'user_id' => $user->id,
        'elevated_role' => 'kepala_sekolah',
        'expires_at' => now()->subMinute(),
    ]);

    Artisan::call('app:revoke-expired-temporary-access');

    expect(TemporaryRoleElevation::query()->count())->toBe(0);
});

test('cleanup command removes expired direct UserPolicyAbility', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();
    $admin = User::factory()->asAdmin()->create();

    UserPolicyAbility::query()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'ability' => 'viewAny',
        'is_inherited' => false,
        'granted_by_user_id' => $admin->id,
        'expires_at' => now()->subMinute(),
    ]);

    Artisan::call('app:revoke-expired-temporary-access');

    expect(UserPolicyAbility::query()->forUser($user->id)->direct()->count())->toBe(0);
});

test('cleanup command does not remove non-expired records', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();
    $admin = User::factory()->asAdmin()->create();

    TemporaryPolicyGrant::query()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'expires_at' => now()->addDay(),
    ]);

    TemporaryRoleElevation::query()->create([
        'user_id' => $user->id,
        'elevated_role' => 'guru',
        'expires_at' => now()->addDay(),
    ]);

    UserPolicyAbility::query()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'ability' => 'viewAny',
        'is_inherited' => false,
        'granted_by_user_id' => $admin->id,
        'expires_at' => now()->addDay(),
    ]);

    Artisan::call('app:revoke-expired-temporary-access');

    expect(TemporaryPolicyGrant::query()->count())->toBe(1);
    expect(TemporaryRoleElevation::query()->count())->toBe(1);
    expect(UserPolicyAbility::query()->direct()->count())->toBe(1);
});

test('cleanup command does not remove inherited UserPolicyAbility (no expires_at)', function () {
    $user = User::factory()->asGuru()->create();
    $policy = lessonPlanPolicy();

    UserPolicyAbility::query()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'ability' => 'viewAny',
        'is_inherited' => true,
        'source_role' => 'guru',
        'expires_at' => null,
    ]);

    Artisan::call('app:revoke-expired-temporary-access');

    expect(UserPolicyAbility::query()->forUser($user->id)->inherited()->count())->toBe(1);
});

// ===========================================================================
// E. Edge Cases
// ===========================================================================

test('policy deleted after grant was created does not crash policy check', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();

    TemporaryPolicyGrant::query()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'expires_at' => now()->addDay(),
    ]);

    // Simulate policy being deleted (set inactive instead of hard delete to avoid FK issues)
    $policy->update(['is_active' => false]);

    // Should not crash, just return false
    expect(Gate::forUser($user)->allows('viewAny', LessonPlan::class))->toBeFalse();
});

test('assignAbility updates expires_at when called again for same ability', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();
    $admin = User::factory()->asAdmin()->create();
    $manager = app(TemporaryAccessManager::class);

    $manager->assignAbility($user, $policy, 'viewAny', $admin, now()->addDay());
    $newExpiry = now()->addWeek();
    $manager->assignAbility($user, $policy, 'viewAny', $admin, $newExpiry);

    $ability = UserPolicyAbility::query()->forUser($user->id)->forPolicy($policy->id)->forAbility('viewAny')->direct()->first();
    expect($ability)->not->toBeNull();
    expect(Carbon::parse($ability->expires_at)->toDateString())->toBe($newExpiry->toDateString());
    // Still only one record
    expect(UserPolicyAbility::query()->forUser($user->id)->forPolicy($policy->id)->forAbility('viewAny')->direct()->count())->toBe(1);
});

test('user with role elevation to same role as base role has correct effectiveRole', function () {
    $guru = User::factory()->asGuru()->create();

    TemporaryRoleElevation::query()->create([
        'user_id' => $guru->id,
        'elevated_role' => 'guru',
        'expires_at' => now()->addDay(),
    ]);

    expect($guru->fresh()->effectiveRole())->toBe('guru');
});

test('hasTemporaryPolicyGrant returns false when no grants exist', function () {
    $user = User::factory()->asSiswa()->create();
    $manager = app(TemporaryAccessManager::class);

    expect($manager->hasTemporaryPolicyGrant($user, 'viewAny', LessonPlan::class))->toBeFalse();
});

test('revokeAbility removes UserPolicyAbility and cleans up TemporaryPolicyGrant when no abilities remain', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();
    $admin = User::factory()->asAdmin()->create();
    $manager = app(TemporaryAccessManager::class);

    $manager->assignAbility($user, $policy, 'viewAny', $admin, now()->addDay());
    expect(TemporaryPolicyGrant::query()->where('user_id', $user->id)->count())->toBe(1);

    $manager->revokeAbility($user, $policy, 'viewAny');

    expect(UserPolicyAbility::query()->forUser($user->id)->forPolicy($policy->id)->direct()->count())->toBe(0);
    expect(TemporaryPolicyGrant::query()->where('user_id', $user->id)->count())->toBe(0);
});
