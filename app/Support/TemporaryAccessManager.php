<?php

namespace App\Support;

use App\Models\AccessPolicy;
use App\Models\TemporaryPolicyGrant;
use App\Models\TemporaryRoleElevation;
use App\Models\User;
use App\Models\UserPolicyAbility;
use Carbon\CarbonInterface;

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
     * Check if user has temporary policy grant via TemporaryPolicyGrant table.
     * Also checks UserPolicyAbility (direct, not-expired) for the same policy/target.
     */
    public function hasTemporaryPolicyGrant(User $user, string $ability, mixed ...$arguments): bool
    {
        $targetModel = $this->resolveTargetModelClass($arguments);

        if (! $targetModel) {
            return false;
        }

        // Check legacy TemporaryPolicyGrant table
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

        // Also check UserPolicyAbility (direct, not-expired) for matching target_model
        $hasDirectAbility = UserPolicyAbility::query()
            ->forUser($user->id)
            ->direct()
            ->notExpired()
            ->whereHas('accessPolicy', function ($q) use ($targetModel): void {
                $q->where('target_model', $targetModel)->where('is_active', true);
            })
            ->forAbility($ability)
            ->exists();

        return $hasDirectAbility;
    }

    /**
     * Check if user has ability either inherited or directly assigned (and not expired).
     */
    public function hasAbility(User $user, AccessPolicy $policy, string $ability): bool
    {
        if ($policy->isAbilityInherited($user, $ability)) {
            return true;
        }

        return $policy->isAbilityDirectAssigned($user, $ability);
    }

    /**
     * Get all abilities user has (inherited + direct) for a policy.
     *
     * @return array<string>
     */
    public function getAvailableAbilities(User $user, AccessPolicy $policy): array
    {
        return $policy->getAvailableAbilities($user);
    }

    /**
     * Get abilities inherited from role.
     *
     * @return array<string>
     */
    public function getInheritedAbilities(User $user, AccessPolicy $policy): array
    {
        return $policy->getInheritedAbilities($user);
    }

    /**
     * Get abilities directly assigned (not expired).
     *
     * @return array<string>
     */
    public function getDirectAbilities(User $user, AccessPolicy $policy): array
    {
        return $policy->getDirectAbilities($user);
    }

    /**
     * Assign ability to user with an expiry date.
     * Also creates a TemporaryPolicyGrant so policies can detect the access.
     * Returns false if ability is already inherited from role.
     * $expiresAt defaults to 1 week from now if not provided.
     */
    public function assignAbility(User $user, AccessPolicy $policy, string $ability, User $grantedBy, ?CarbonInterface $expiresAt = null): bool
    {
        // Don't allow assigning if already inherited from role
        if ($policy->isAbilityInherited($user, $ability)) {
            return false;
        }

        $expiresAt ??= now()->addWeek();

        // Upsert UserPolicyAbility (update expires_at if already exists)
        UserPolicyAbility::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'access_policy_id' => $policy->id,
                'ability' => $ability,
                'is_inherited' => false,
            ],
            [
                'granted_by_user_id' => $grantedBy->id,
                'expires_at' => $expiresAt,
            ]
        );

        // Sync TemporaryPolicyGrant so hasTemporaryPolicyGrant() in policies works
        TemporaryPolicyGrant::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'access_policy_id' => $policy->id,
            ],
            [
                'granted_by_user_id' => $grantedBy->id,
                'expires_at' => $expiresAt,
            ]
        );

        return true;
    }

    /**
     * Elevate user role temporarily.
     * Prevents privilege escalation: elevated_role must not exceed grantedBy's role level.
     * Deduplicates: updates expires_at if an active elevation for the same role already exists.
     */
    public function elevateRole(User $user, string $elevatedRole, User $grantedBy, CarbonInterface $expiresAt): bool
    {
        $grantorLevel = self::ROLE_LEVELS[$grantedBy->effectiveRole()] ?? 0;
        $targetLevel = self::ROLE_LEVELS[$elevatedRole] ?? 0;

        if ($targetLevel >= $grantorLevel) {
            return false;
        }

        TemporaryRoleElevation::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'elevated_role' => $elevatedRole,
            ],
            [
                'granted_by_user_id' => $grantedBy->id,
                'expires_at' => $expiresAt,
            ]
        );

        return true;
    }

    /**
     * Revoke ability from user (only removes direct assignments).
     */
    public function revokeAbility(User $user, AccessPolicy $policy, string $ability): bool
    {
        if ($policy->isAbilityInherited($user, $ability)) {
            return false;
        }

        UserPolicyAbility::query()
            ->forUser($user->id)
            ->forPolicy($policy->id)
            ->forAbility($ability)
            ->direct()
            ->delete();

        // Also remove the corresponding TemporaryPolicyGrant if no more abilities remain
        $remainingAbilities = UserPolicyAbility::query()
            ->forUser($user->id)
            ->forPolicy($policy->id)
            ->direct()
            ->notExpired()
            ->count();

        if ($remainingAbilities === 0) {
            TemporaryPolicyGrant::query()
                ->where('user_id', $user->id)
                ->where('access_policy_id', $policy->id)
                ->delete();
        }

        return true;
    }

    /**
     * Build inherited permissions from role (called during role assignment).
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

        foreach ($policies as $policy) {
            $abilities = $policy->getAllAbilities();

            if (in_array('*', $abilities, true)) {
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
     * Get the numeric level for a role.
     */
    public function getRoleLevel(string $role): int
    {
        return self::ROLE_LEVELS[$role] ?? 0;
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
