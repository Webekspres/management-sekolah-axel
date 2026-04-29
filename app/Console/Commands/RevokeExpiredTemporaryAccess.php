<?php

namespace App\Console\Commands;

use App\Models\TemporaryPolicyGrant;
use App\Models\TemporaryRoleElevation;
use App\Models\UserPolicyAbility;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:revoke-expired-temporary-access')]
#[Description('Revoke expired temporary role elevations, policy grants, and user policy abilities')]
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

        // Only delete direct (temporary) abilities that have an expires_at set and are past due.
        // Inherited abilities (is_inherited = true) have no expires_at and must not be deleted here.
        $revokedPolicyAbilities = UserPolicyAbility::query()
            ->where('is_inherited', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->delete();

        $this->info("Temporary policy grants revoked: {$revokedPolicyGrants}");
        $this->info("Temporary role elevations revoked: {$revokedRoleElevations}");
        $this->info("Temporary policy abilities revoked: {$revokedPolicyAbilities}");

        return self::SUCCESS;
    }
}
