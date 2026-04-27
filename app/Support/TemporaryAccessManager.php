<?php

namespace App\Support;

use App\Models\AccessPolicy;
use App\Models\TemporaryPolicyGrant;
use App\Models\User;
use App\Models\UserPolicyAbility;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class TemporaryAccessManager
{
    public function resolveEffectiveRole(User $user): string
    {
        return $user->role;
    }

    /**
     * Check if user has temporary policy grant for a given ability and target model.
     * Checks both TemporaryPolicyGrant (legacy) and UserPolicyAbility (direct, not-expired).
     * If $levelId is provided, only grants scoped to that level (or unscoped) are considered.
     */
    public function hasTemporaryPolicyGrant(User $user, string $ability, mixed ...$arguments): bool
    {
        $targetModel = $this->resolveTargetModelClass($arguments);

        if (! $targetModel) {
            return false;
        }

        // Resolve level from the target model instance if available
        $levelId = $this->resolveLevelId($arguments);

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

        // Check UserPolicyAbility (direct, not-expired, matching level or unscoped)
        $query = UserPolicyAbility::query()
            ->forUser($user->id)
            ->direct()
            ->notExpired()
            ->forAbility($ability)
            ->whereHas('accessPolicy', function ($q) use ($targetModel): void {
                $q->where('target_model', $targetModel)->where('is_active', true);
            });

        if ($levelId !== null) {
            // Match abilities scoped to this level OR unscoped (null = all levels)
            $query->where(function ($q) use ($levelId): void {
                $q->whereNull('level_id')->orWhere('level_id', $levelId);
            });
        }

        return $query->exists();
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
     * Assign ability to user with an expiry date and optional level scope.
     * Also creates/updates a TemporaryPolicyGrant so legacy policy checks work.
     * Returns false if ability is already inherited from role.
     * $expiresAt defaults to 1 week from now if not provided.
     */
    public function assignAbility(
        User $user,
        AccessPolicy $policy,
        string $ability,
        User $grantedBy,
        ?CarbonInterface $expiresAt = null,
        ?string $levelId = null,
    ): bool {
        if ($policy->isAbilityInherited($user, $ability)) {
            return false;
        }

        $expiresAt ??= now()->addWeek();

        // Upsert UserPolicyAbility — unique per user+policy+ability+is_inherited+level_id
        UserPolicyAbility::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'access_policy_id' => $policy->id,
                'ability' => $ability,
                'is_inherited' => false,
                'level_id' => $levelId,
            ],
            [
                'granted_by_user_id' => $grantedBy->id,
                'expires_at' => $expiresAt,
            ]
        );

        // Sync TemporaryPolicyGrant so hasTemporaryPolicyGrant() in legacy policies works
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
     * Revoke ability from user (only removes direct assignments).
     */
    public function revokeAbility(User $user, AccessPolicy $policy, string $ability, ?string $levelId = null): bool
    {
        if ($policy->isAbilityInherited($user, $ability)) {
            return false;
        }

        $query = UserPolicyAbility::query()
            ->forUser($user->id)
            ->forPolicy($policy->id)
            ->forAbility($ability)
            ->direct();

        if ($levelId !== null) {
            $query->where('level_id', $levelId);
        }

        $query->delete();

        // Remove TemporaryPolicyGrant if no more active direct abilities remain
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
        UserPolicyAbility::query()
            ->forUser($user->id)
            ->inherited()
            ->delete();

        $policies = AccessPolicy::query()
            ->active()
            ->get()
            ->filter(fn (AccessPolicy $policy) => $policy->isPermanentForRole($user->role));

        foreach ($policies as $policy) {
            $abilities = $policy->getAllAbilities();

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
     * Get level IDs that a user is restricted to via temporary access for a given model.
     * Returns null if user has no level restriction (access to all levels).
     * Returns an empty collection if user has no temporary access at all.
     *
     * @return Collection<int, string>|null
     */
    public function getAllowedLevelIds(User $user, string $targetModel): ?Collection
    {
        $abilities = UserPolicyAbility::query()
            ->forUser($user->id)
            ->direct()
            ->notExpired()
            ->whereHas('accessPolicy', fn ($q) => $q->where('target_model', $targetModel)->where('is_active', true))
            ->get();

        if ($abilities->isEmpty()) {
            return null;
        }

        // If any ability has null level_id, user has unrestricted level access
        if ($abilities->whereNull('level_id')->isNotEmpty()) {
            return null;
        }

        return $abilities->pluck('level_id')->filter()->unique()->values();
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

    /**
     * Try to resolve a level_id from the target model instance.
     * Returns null if not resolvable (means "all levels").
     *
     * @param  array<int, mixed>  $arguments
     */
    private function resolveLevelId(array $arguments): ?string
    {
        $target = $arguments[0] ?? null;

        if (! is_object($target)) {
            return null;
        }

        // Models that have a direct level_id column
        if (property_exists($target, 'level_id') || isset($target->level_id)) {
            return $target->level_id ?? null;
        }

        // Models that belong to a SchoolClass which has a level_id
        if (method_exists($target, 'schoolClass') && $target->relationLoaded('schoolClass')) {
            return $target->schoolClass?->level_id ?? null;
        }

        // Schedule → class → level_id
        if (method_exists($target, 'schoolClass')) {
            return $target->schoolClass?->level_id ?? null;
        }

        return null;
    }
}
