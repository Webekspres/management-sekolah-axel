<?php

namespace App\Models;

use App\HasUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'access_policy_id',
    'ability',
    'is_inherited',
    'source_role',
    'granted_by_user_id',
    'expires_at',
    'level_id',
])]
class UserPolicyAbility extends Model
{
    use HasFactory, HasUlid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'is_inherited' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accessPolicy(): BelongsTo
    {
        return $this->belongsTo(AccessPolicy::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function scopeDirect(Builder $query): Builder
    {
        return $query->where('is_inherited', false);
    }

    public function scopeInherited(Builder $query): Builder
    {
        return $query->where('is_inherited', true);
    }

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPolicy(Builder $query, string $policyId): Builder
    {
        return $query->where('access_policy_id', $policyId);
    }

    public function scopeForAbility(Builder $query, string $ability): Builder
    {
        return $query->where('ability', $ability);
    }

    /**
     * Scope to only non-expired abilities (permanent or still active).
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
