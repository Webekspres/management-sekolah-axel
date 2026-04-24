<?php

use App\Models\AccessPolicy;
use App\Models\User;
use App\Models\UserPolicyAbility;
use App\Support\TemporaryAccessManager;

describe('Authorization Refactor - Policies + Abilities Based System', function () {
    beforeEach(function () {
        $this->manager = app(TemporaryAccessManager::class);
    });

    test('user policy ability model stores direct assignments', function () {
        $user = User::factory()->create(['role' => 'guru']);
        $policy = AccessPolicy::query()->active()->first();

        if (! $policy) {
            $this->markTestSkipped('No active policy found');
        }

        UserPolicyAbility::create([
            'user_id' => $user->id,
            'access_policy_id' => $policy->id,
            'ability' => 'create',
            'is_inherited' => false,
            'granted_by_user_id' => User::where('role', 'super_admin')->first()?->id ?? User::factory()->create(['role' => 'super_admin'])->id,
        ]);

        $record = UserPolicyAbility::forUser($user->id)->forPolicy($policy->id)->first();

        expect($record)->not->toBeNull();
        expect($record->ability)->toBe('create');
        expect($record->is_inherited)->toBeFalse();
    });

    test('access policy can identify inherited abilities for role', function () {
        $user = User::factory()->create(['role' => 'guru']);
        $policy = AccessPolicy::query()->active()->first();

        if (! $policy) {
            $this->markTestSkipped('No active policy found');
        }

        $inherited = $policy->getInheritedAbilities($user);

        expect($inherited)->toBeArray();
    });

    test('access policy can get direct assignments', function () {
        $user = User::factory()->create(['role' => 'siswa_ortu']);
        $policy = AccessPolicy::query()->active()->first();

        if (! $policy) {
            $this->markTestSkipped('No active policy found');
        }

        $grantedBy = User::where('role', 'super_admin')->first() ?? User::factory()->create(['role' => 'super_admin']);

        UserPolicyAbility::create([
            'user_id' => $user->id,
            'access_policy_id' => $policy->id,
            'ability' => 'read',
            'is_inherited' => false,
            'granted_by_user_id' => $grantedBy->id,
        ]);

        $direct = $policy->getDirectAbilities($user);

        expect($direct)->toContain('read');
    });

    test('temporary access manager can assign ability', function () {
        $user = User::factory()->create(['role' => 'siswa_ortu']);
        $policy = AccessPolicy::query()->active()->first();

        if (! $policy) {
            $this->markTestSkipped('No active policy found');
        }

        $grantedBy = User::where('role', 'super_admin')->first() ?? User::factory()->create(['role' => 'super_admin']);

        $result = $this->manager->assignAbility($user, $policy, 'create', $grantedBy);

        expect($result)->toBeTrue();
        expect($policy->isAbilityDirectAssigned($user, 'create'))->toBeTrue();
    });

    test('temporary access manager cannot assign inherited ability', function () {
        $user = User::factory()->create(['role' => 'guru']);
        $policy = AccessPolicy::query()->active()
            ->where('permanent_roles', 'like', '%guru%')
            ->first();

        if (! $policy) {
            $this->markTestSkipped('No policy with guru role found');
        }

        $grantedBy = User::where('role', 'super_admin')->first() ?? User::factory()->create(['role' => 'super_admin']);

        // Get an inherited ability
        $inherited = $policy->getInheritedAbilities($user);
        if (empty($inherited)) {
            $this->markTestSkipped('User has no inherited abilities');
        }

        $testAbility = reset($inherited);

        // Try to assign an inherited ability
        $result = $this->manager->assignAbility($user, $policy, $testAbility, $grantedBy);

        expect($result)->toBeFalse();
    });

    test('user policy ability has correct scopes', function () {
        $user = User::factory()->create();
        $policy = AccessPolicy::query()->active()->first();

        if (! $policy) {
            $this->markTestSkipped('No active policy found');
        }

        $directRecord = UserPolicyAbility::factory()->create([
            'user_id' => $user->id,
            'access_policy_id' => $policy->id,
            'is_inherited' => false,
        ]);

        expect(UserPolicyAbility::forUser($user->id)->count())->toBeGreaterThanOrEqual(1);
        expect(UserPolicyAbility::forPolicy($policy->id)->count())->toBeGreaterThanOrEqual(1);
        expect(UserPolicyAbility::direct()->count())->toBeGreaterThanOrEqual(1);
    });
});
