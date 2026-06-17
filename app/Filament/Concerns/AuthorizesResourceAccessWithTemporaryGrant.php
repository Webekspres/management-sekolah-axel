<?php

namespace App\Filament\Concerns;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\TemporaryAccessManager;

trait AuthorizesResourceAccessWithTemporaryGrant
{
    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        $model = static::getModel();

        if ($user->can('viewAny', $model)) {
            return true;
        }

        return app(TemporaryAccessManager::class)
            ->hasTemporaryPolicyGrant($user, 'viewAny', $model);
    }

    /**
     * @param  list<UserRole>  $roles
     */
    protected static function userHasPanelRole(User $user, UserRole ...$roles): bool
    {
        return $user->hasUserRole(...$roles);
    }
}
