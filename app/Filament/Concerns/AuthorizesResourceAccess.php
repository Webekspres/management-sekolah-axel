<?php

namespace App\Filament\Concerns;

trait AuthorizesResourceAccess
{
    public static function canAccess(): bool
    {
        $model = static::getModel();

        return auth()->user()?->can('viewAny', $model) ?? false;
    }
}
