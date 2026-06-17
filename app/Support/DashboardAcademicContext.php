<?php

namespace App\Support;

use App\Models\Level;
use Illuminate\Support\Facades\Session;

/**
 * Shared label for dashboard widgets when the academic level switcher is active.
 */
final class DashboardAcademicContext
{
    private static ?string $statsSuffixCache = null;

    public static function statsSuffix(): string
    {
        if (self::$statsSuffixCache !== null) {
            return self::$statsSuffixCache;
        }

        $levelId = Session::get('active_academic_level_id');

        if (! $levelId) {
            return self::$statsSuffixCache = '';
        }

        $name = Level::query()->whereKey($levelId)->value('name');

        return self::$statsSuffixCache = $name ? ' — Level: '.$name : '';
    }

    public static function resetCache(): void
    {
        self::$statsSuffixCache = null;
    }
}
