<?php

namespace App\Support;

use App\Models\AccessPolicy;
use App\Models\TemporaryPolicyGrant;
use App\Models\TemporaryRoleElevation;
use App\Models\User;

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
