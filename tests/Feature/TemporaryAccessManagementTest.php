<?php

use App\Filament\Pages\TemporaryAccessManagement;
use App\Models\AccessPolicy;
use App\Models\LessonPlan;
use App\Models\TemporaryPolicyGrant;
use App\Models\TemporaryRoleElevation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('temporary policy grant can allow gated policy ability', function () {
    $user = User::factory()->asSiswa()->create();
    $policy = AccessPolicy::query()->where('code', 'lesson_plan_management')->firstOrFail();

    expect(Gate::forUser($user)->allows('viewAny', LessonPlan::class))->toBeFalse();

    TemporaryPolicyGrant::query()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'expires_at' => now()->addDay(),
    ]);

    expect(Gate::forUser($user)->allows('viewAny', LessonPlan::class))->toBeTrue();
});

test('temporary role elevation affects effective role and panel access', function () {
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

test('expired temporary access is auto revoked by command', function () {
    $user = User::factory()->asGuru()->create();
    $policy = AccessPolicy::query()->where('code', 'kbm_management')->firstOrFail();

    TemporaryPolicyGrant::query()->create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'expires_at' => now()->subMinute(),
    ]);

    TemporaryRoleElevation::query()->create([
        'user_id' => $user->id,
        'elevated_role' => 'kepala_sekolah',
        'expires_at' => now()->subMinute(),
    ]);

    Artisan::call('app:revoke-expired-temporary-access');

    expect(TemporaryPolicyGrant::query()->count())->toBe(0)
        ->and(TemporaryRoleElevation::query()->count())->toBe(0);
});

test('temporary access page skips permanent policy grants', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $admin = User::factory()->asAdmin()->create();
    $targetUser = User::factory()->asAdmin()->create();
    $policy = AccessPolicy::query()->where('code', 'announcement_management')->firstOrFail();

    $this->actingAs($admin);

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [$targetUser->id])
        ->set('data.policy_ids', [$policy->id])
        ->set('data.duration', '1_week')
        ->call('submit');

    expect(TemporaryPolicyGrant::query()
        ->where('user_id', $targetUser->id)
        ->where('access_policy_id', $policy->id)
        ->count())->toBe(0);
});

test('temporary access page can assign temporary role without selecting abilities', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $admin = User::factory()->asAdmin()->create();
    $targetUser = User::factory()->asSiswa()->create();

    $this->actingAs($admin);

    Livewire::test(TemporaryAccessManagement::class)
        ->set('data.user_ids', [$targetUser->id])
        ->set('data.temporary_role', 'guru')
        ->set('data.duration', '1_week')
        ->call('submit');

    expect(TemporaryRoleElevation::query()
        ->where('user_id', $targetUser->id)
        ->where('elevated_role', 'guru')
        ->count())->toBe(1);
});
