<?php

use App\Filament\Pages\TemporaryAccessManagement;
use App\Models\AccessPolicy;
use App\Models\LessonPlan;
use App\Models\Level;
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

    expect(Gate::forUser($user)->allows('viewAny', LessonPlan::class))->toBeFalse();

    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, now()->addDay());

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

    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, now()->addDay());
    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, now()->addDay());

    expect(UserPolicyAbility::query()->forUser($user->id)->forPolicy($policy->id)->forAbility('viewAny')->direct()->count())->toBe(1);
    expect(TemporaryPolicyGrant::query()->where('user_id', $user->id)->where('access_policy_id', $policy->id)->count())->toBe(1);
});

test('assignAbility returns false and skips when ability is inherited from role', function () {
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

    $policy->update(['is_active' => false]);

    TemporaryPolicyGrant::query()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'expires_at' => now()->addDay(),
    ]);

    expect(Gate::forUser($user)->allows('viewAny', LessonPlan::class))->toBeFalse();
});

// ===========================================================================
// B. Level-scoped access
// ===========================================================================

test('assignAbility with level_id only grants access for that level', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();
    $admin = User::factory()->asAdmin()->create();
    $level = Level::query()->first();

    if (! $level) {
        $this->markTestSkipped('No level found');
    }

    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, now()->addDay(), $level->id);

    // UserPolicyAbility scoped to this level exists
    expect(UserPolicyAbility::query()
        ->forUser($user->id)
        ->forPolicy($policy->id)
        ->forAbility('viewAny')
        ->direct()
        ->where('level_id', $level->id)
        ->count())->toBe(1);
});

test('assignAbility with null level_id grants access to all levels', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();
    $admin = User::factory()->asAdmin()->create();

    app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, now()->addDay(), null);

    expect(UserPolicyAbility::query()
        ->forUser($user->id)
        ->forPolicy($policy->id)
        ->forAbility('viewAny')
        ->direct()
        ->whereNull('level_id')
        ->count())->toBe(1);

    // Gate check passes (unscoped = all levels)
    expect(Gate::forUser($user)->allows('viewAny', LessonPlan::class))->toBeTrue();
});

// ===========================================================================
// C. effectiveRole — now returns base role directly (no elevation)
// ===========================================================================

test('effectiveRole returns the user base role', function () {
    $guru = User::factory()->asGuru()->create();
    $siswa = User::factory()->asSiswa()->create();
    $admin = User::factory()->asAdmin()->create();

    expect($guru->effectiveRole())->toBe('guru');
    expect($siswa->effectiveRole())->toBe('siswa_ortu');
    expect($admin->effectiveRole())->toBe('super_admin');
});

test('TemporaryRoleElevation records do not affect effectiveRole anymore', function () {
    $user = User::factory()->asGuru()->create();

    TemporaryRoleElevation::query()->create([
        'user_id' => $user->id,
        'elevated_role' => 'kepala_sekolah',
        'expires_at' => now()->addDay(),
    ]);

    // effectiveRole now returns base role, not elevated role
    expect($user->fresh()->effectiveRole())->toBe('guru');
});

// ===========================================================================
// D. Form Validation
// ===========================================================================

test('submit rejects custom_expires_at in the past', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $target = User::factory()->asSiswa()->create();
    $this->actingAs($admin);

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [$target->id])
        ->set('data.duration', 'custom')
        ->set('data.custom_expires_at', now()->subHour()->toDateTimeString())
        ->call('submit')
        ->assertHasErrors(['data.custom_expires_at']);
});

test('submit rejects when no ability is selected', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $target = User::factory()->asSiswa()->create();
    $this->actingAs($admin);

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [$target->id])
        ->set('data.duration', '1_week')
        ->call('submit')
        ->assertNotified();

    expect(UserPolicyAbility::query()->direct()->count())->toBe(0);
});

test('submit rejects when user_ids is empty', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $this->actingAs($admin);

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [])
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
    $options = $component->instance()->getUserOptions();

    expect(array_key_exists($inactiveUser->id, $options))->toBeFalse();
});

test('form does not have temporary_role field anymore', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $this->actingAs($admin);

    $component = Livewire::test(TemporaryAccessManagement::class);

    // Setting temporary_role should have no effect — no such field in form
    expect(isset($component->instance()->data['temporary_role']))->toBeFalse();
});

test('submit assigns ability via form and creates grant', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $target = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();
    $this->actingAs($admin);

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [$target->id])
        ->set("data.policy_abilities.{$policy->id}", ['viewAny'])
        ->set('data.duration', '1_week')
        ->call('submit');

    expect(UserPolicyAbility::query()
        ->forUser($target->id)
        ->forPolicy($policy->id)
        ->forAbility('viewAny')
        ->direct()
        ->count())->toBe(1);

    expect(TemporaryPolicyGrant::query()
        ->where('user_id', $target->id)
        ->where('access_policy_id', $policy->id)
        ->count())->toBe(1);
});

test('submit skips inherited abilities and does not create duplicate records', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $guru = User::factory()->asGuru()->create();
    $policy = announcementPolicy();
    $this->actingAs($admin);

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [$guru->id])
        ->set("data.policy_abilities.{$policy->id}", ['viewAny'])
        ->set('data.duration', '1_week')
        ->call('submit');

    expect(UserPolicyAbility::query()->forUser($guru->id)->forPolicy($policy->id)->direct()->count())->toBe(0);
});

// ===========================================================================
// E. Cleanup Command
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
    expect(UserPolicyAbility::query()->direct()->count())->toBe(1);
});

test('cleanup command does not remove inherited UserPolicyAbility', function () {
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
// F. New Policies — smoke test
// ===========================================================================

test('all 11 access policies exist in database', function () {
    $expectedCodes = [
        'announcement_management',
        'lesson_plan_management',
        'lesson_plan_review',
        'kbm_management',
        'kbm_review',
        'teacher_management',
        'student_management',
        'academic_year_management',
        'schedule_management',
        'school_class_management',
        'subject_management',
    ];

    foreach ($expectedCodes as $code) {
        expect(AccessPolicy::query()->where('code', $code)->exists())
            ->toBeTrue("Policy '{$code}' tidak ditemukan di database");
    }
});

test('assignAbility works for new teacher_management policy', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = AccessPolicy::query()->where('code', 'teacher_management')->firstOrFail();
    $admin = User::factory()->asAdmin()->create();

    $result = app(TemporaryAccessManager::class)->assignAbility($user, $policy, 'viewAny', $admin, now()->addDay());

    expect($result)->toBeTrue();
    expect(UserPolicyAbility::query()->forUser($user->id)->forPolicy($policy->id)->direct()->count())->toBe(1);
});

// ===========================================================================
// G. Edge Cases
// ===========================================================================

test('policy deactivated after grant does not crash policy check', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = lessonPlanPolicy();

    TemporaryPolicyGrant::query()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'expires_at' => now()->addDay(),
    ]);

    $policy->update(['is_active' => false]);

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
    expect(UserPolicyAbility::query()->forUser($user->id)->forPolicy($policy->id)->forAbility('viewAny')->direct()->count())->toBe(1);
});

test('hasTemporaryPolicyGrant returns false when no grants exist', function () {
    $user = User::factory()->asSiswa()->create();

    expect(app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, 'viewAny', LessonPlan::class))->toBeFalse();
});

test('revokeAbility removes UserPolicyAbility and cleans up TemporaryPolicyGrant', function () {
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
