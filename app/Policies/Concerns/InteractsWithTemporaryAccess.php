<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Support\TemporaryAccessManager;

trait InteractsWithTemporaryAccess
{
    private function hasTemporaryAccess(User $user, string $ability, mixed $target): bool
    {
        return app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, $ability, $target);
    }
}
