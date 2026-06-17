<?php

namespace App\Filament\Concerns;

use App\Enums\UserRole;

trait AuthorizesAdminOnlyResourceAccess
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasUserRole(UserRole::SuperAdmin) ?? false;
    }
}
