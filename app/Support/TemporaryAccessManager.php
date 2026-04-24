<?php

namespace App\Support;

use App\Models\AccessPolicy;
use App\Models\TemporaryPolicyGrant;
use App\Models\TemporaryRoleElevation;
use App\Models\User;
use App\Models\UserPolicyAbility;

class TemporaryAccessManager
{
    /**
     * @var array<string, int>
     */
    private const ROLE_LEVELS = [
        'siswa_ortu' => 1,
        'guru' => 2,
        'kepala_sekolah' => 3,
        'super_admin' => 4,
    ];

    public function resolveEffectiveRole(User $user): string
    {
        $roles = [$user->role];

        $elevatedRoles = TemporaryRoleElevation::query()
            ->where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->pluck('elevated_role')
            ->all();

        $roles = array_merge($roles, $elevatedRoles);

        $effectiveRole = collect($roles)
            ->filter(fn (string $role): bool => array_key_exists($role, self::ROLE_LEVELS))
            ->sortByDesc(fn (string $role): int => self::ROLE_LEVELS[$role])
            ->first() ?? $user->role;

        return $effectiveRole;
    }

    /**
     * Check if user has temporary policy grant (for backwards compatibility)
     */
    public function hasTemporaryPolicyGrant(User $user, string $ability, mixed ...$arguments): bool
    {
        $targetModel = $this->resolveTargetModelClass($arguments);

        if (! $targetModel) {
            return false;
        }

        $activeGrants = TemporaryPolicyGrant::query()
            ->where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->with('accessPolicy')
            ->get();

        foreach ($activeGrants as $grant) {
            $policy = $grant->accessPolicy;

            if (! $policy || ! $policy->is_active) {
                continue;
            }

            if ($policy->target_model !== $targetModel) {
                continue;
            }

            if ($policy->supportsAbility($ability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has ability either inherited or directly assigned
     */
    public function hasAbility(User $user, AccessPolicy $policy, string $ability): bool
    {
        // Check inherited abilities (from role)
        if ($policy->isAbilityInherited($user, $ability)) {
            return true;
        }

        // Check directly assigned abilities
        return $policy->isAbilityDirectAssigned($user, $ability);
    }

    /**
     * Get all abilities user has (inherited + direct) for a policy
     *
     * @return array<string>
     */
    public function getAvailableAbilities(User $user, AccessPolicy $policy): array
    {
        return $policy->getAvailableAbilities($user);
    }

    /**
     * Get abilities inherited from role
     *
     * @return array<string>
     */
    public function getInheritedAbilities(User $user, AccessPolicy $policy): array
    {
        return $policy->getInheritedAbilities($user);
    }

    /**
     * Get abilities directly assigned
     *
     * @return array<string>
     */
    public function getDirectAbilities(User $user, AccessPolicy $policy): array
    {
        return $policy->getDirectAbilities($user);
    }

    /**
     * Assign ability to user (creates delta only if not inherited)
     */
    public function assignAbility(User $user, AccessPolicy $policy, string $ability, User $grantedBy): bool
    {
        // Don't allow assigning if already inherited from role
        if ($policy->isAbilityInherited($user, $ability)) {
            return false;
        }

        // Check if already directly assigned
        $exists = UserPolicyAbility::query()
            ->forUser($user->id)
            ->forPolicy($policy->id)
            ->forAbility($ability)
            ->direct()
            ->exists();

        if ($exists) {
            return true; // Already assigned
        }

        // Create direct assignment
        UserPolicyAbility::create([
            'user_id' => $user->id,
            'access_policy_id' => $policy->id,
            'ability' => $ability,
            'is_inherited' => false,
            'granted_by_user_id' => $grantedBy->id,
        ]);

        return true;
    }

    /**
     * Revoke ability from user (only removes direct assignments)
     */
    public function revokeAbility(User $user, AccessPolicy $policy, string $ability): bool
    {
        // Cannot revoke inherited abilities
        if ($policy->isAbilityInherited($user, $ability)) {
            return false;
        }

        UserPolicyAbility::query()
            ->forUser($user->id)
            ->forPolicy($policy->id)
            ->forAbility($ability)
            ->direct()
            ->delete();

        return true;
    }

    /**
     * Build inherited permissions from role (called during role assignment)
     */
    public function rebuildInheritedAbilities(User $user): void
    {
        // First, clear all inherited abilities for this user
        UserPolicyAbility::query()
            ->forUser($user->id)
            ->inherited()
            ->delete();

        // Get all policies that are permanent for this role
        $policies = AccessPolicy::query()
            ->active()
            ->get()
            ->filter(fn (AccessPolicy $policy) => $policy->isPermanentForRole($user->role));

        // Create inherited ability records for each policy
        foreach ($policies as $policy) {
            $abilities = $policy->getAllAbilities();

            if (in_array('*', $abilities, true)) {
                // Handle wildcard - store all available abilities
                $abilities = $policy->getAllAbilities();
            }

            foreach ($abilities as $ability) {
                UserPolicyAbility::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'access_policy_id' => $policy->id,
                        'ability' => $ability,
                        'is_inherited' => true,
                    ],
                    [
                        'source_role' => $user->role,
                    ]
                );
            }
        }
    }

    public function isPermanentlyAllowedByRole(User $user, AccessPolicy $policy): bool
    {
        return $policy->isPermanentForRole($user->role);
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    private function resolveTargetModelClass(array $arguments): ?string
    {
        $target = $arguments[0] ?? null;

        if (is_object($target)) {
            return $target::class;
        }

        if (is_string($target) && class_exists($target)) {
            return $target;
        }

        return null;
    }
}
