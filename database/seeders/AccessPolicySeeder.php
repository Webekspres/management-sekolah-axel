<?php

namespace Database\Seeders;

use App\Support\AccessPolicyRegistry;
use Illuminate\Database\Seeder;

class AccessPolicySeeder extends Seeder
{
    /**
     * Idempotent seeder — upserts all access policies.
     * On staging/production without terminal, the same data is applied via migration.
     */
    public function run(): void
    {
        AccessPolicyRegistry::sync();
    }
}
