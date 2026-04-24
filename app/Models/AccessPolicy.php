<?php

namespace App\Models;

use App\HasUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
    use HasUlid;

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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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
