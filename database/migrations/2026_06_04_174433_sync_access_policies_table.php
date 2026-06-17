<?php

use App\Support\AccessPolicyRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sync access_policies without terminal/seed on cPanel staging.
 * Runs automatically when deploy hook hits /deploy/{token}/release (migrate --force).
 */
return new class extends Migration
{
    public function up(): void
    {
        AccessPolicyRegistry::sync();
    }

    public function down(): void
    {
        DB::table('access_policies')
            ->whereIn('code', AccessPolicyRegistry::codesAddedAfterInitialRelease())
            ->delete();
    }
};
