<?php

namespace App\Console\Commands;

use App\Models\TemporaryPolicyGrant;
use App\Models\TemporaryRoleElevation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:revoke-expired-temporary-access')]
#[Description('Revoke expired temporary role elevations and policy grants')]
class RevokeExpiredTemporaryAccess extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();

        $revokedPolicyGrants = TemporaryPolicyGrant::query()
            ->where('expires_at', '<=', $now)
            ->delete();

        $revokedRoleElevations = TemporaryRoleElevation::query()
            ->where('expires_at', '<=', $now)
            ->delete();

        $this->info("Temporary policy grants revoked: {$revokedPolicyGrants}");
        $this->info("Temporary role elevations revoked: {$revokedRoleElevations}");

        return self::SUCCESS;
    }
}
