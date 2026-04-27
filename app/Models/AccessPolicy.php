<?php

namespace App\Models;

use App\HasUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'description',
    'target_model',
    'abilities',
    'permanent_roles',
    'is_active',
])]
class AccessPolicy extends Model
{
    use HasFactory, HasUlid;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'permanent_roles' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function temporaryGrants(): HasMany
    {
        return $this->hasMany(TemporaryPolicyGrant::class);
    }

    public function userPolicyAbilities(): HasMany
    {
        return $this->hasMany(UserPolicyAbility::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all abilities supported by this policy
     *
     * @return array<string>
     */
    public function getAllAbilities(): array
    {
        $abilities = $this->abilities ?? [];

        if (in_array('*', $abilities, true)) {
            return ['*'];
        }

        return $abilities;
    }

    /**
     * Get inherited abilities for a user based on their role
     *
     * @return array<string>
     */
    public function getInheritedAbilities(User $user): array
    {
        if ($this->isPermanentForRole($user->role)) {
            return $this->getAllAbilities();
        }

        return [];
    }

    /**
     * Get direct (manually assigned) abilities for a user that are not expired.
     *
     * @return array<string>
     */
    public function getDirectAbilities(User $user): array
    {
        return UserPolicyAbility::query()
            ->forUser($user->id)
            ->forPolicy($this->id)
            ->direct()
            ->notExpired()
            ->pluck('ability')
            ->toArray();
    }

    /**
     * Get all available abilities for a user (inherited + direct)
     *
     * @return array<string>
     */
    public function getAvailableAbilities(User $user): array
    {
        $inherited = $this->getInheritedAbilities($user);
        $direct = $this->getDirectAbilities($user);

        return array_values(array_unique(array_merge($inherited, $direct)));
    }

    /**
     * Check if ability is inherited from role
     */
    public function isAbilityInherited(User $user, string $ability): bool
    {
        if ($this->isPermanentForRole($user->role)) {
            return $this->supportsAbility($ability);
        }

        return false;
    }

    /**
     * Check if ability is directly assigned and not expired.
     */
    public function isAbilityDirectAssigned(User $user, string $ability): bool
    {
        return UserPolicyAbility::query()
            ->forUser($user->id)
            ->forPolicy($this->id)
            ->forAbility($ability)
            ->direct()
            ->notExpired()
            ->exists();
    }

    public function supportsAbility(string $ability): bool
    {
        $abilities = $this->abilities ?? [];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    public function isPermanentForRole(string $role): bool
    {
        return in_array($role, $this->permanent_roles ?? [], true);
    }
}
