<?php

use App\Filament\Pages\TemporaryAccessLogList;
use App\Models\AccessPolicy;
use App\Models\TemporaryAccessLog;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

// ---------------------------------------------------------------------------
// Setup
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->admin = User::factory()->asAdmin()->create();
    actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Buat TemporaryAccessLog dengan data minimal yang valid.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeLog(array $overrides = []): TemporaryAccessLog
{
    $policy = AccessPolicy::factory()->create();
    $user = User::factory()->asSiswa()->create();
    $grantedBy = User::factory()->asAdmin()->create();

    return TemporaryAccessLog::create(array_merge([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'ability' => 'viewAny',
        'level_id' => null,
        'granted_by_user_id' => $grantedBy->id,
        'granted_at' => now(),
        'expires_at' => now()->addWeek(),
        'revoked_at' => null,
        'revoked_by_user_id' => null,
    ], $overrides));
}

// ===========================================================================
// Requirement 3.4 — Akses halaman hanya untuk super_admin
// ===========================================================================

test('user non-admin tidak dapat mengakses halaman log akses', function () {
    $nonAdmin = User::factory()->asGuru()->create();

    actingAs($nonAdmin);

    $this->get('/admin/log-akses')->assertForbidden();
});

// ===========================================================================
// Requirement 3.5 — Tabel menampilkan log yang ada
// ===========================================================================

test('tabel menampilkan semua log yang ada', function () {
    $logs = collect([
        makeLog(),
        makeLog(),
        makeLog(),
    ]);

    Livewire::test(TemporaryAccessLogList::class)
        ->assertCanSeeTableRecords($logs);
});

// ===========================================================================
// Requirement 3.6 — Search berdasarkan nama user
// ===========================================================================

test('search berdasarkan nama user menampilkan hasil yang benar', function () {
    $targetUser = User::factory()->asSiswa()->create(['name' => 'Budi Santoso']);
    $otherUser = User::factory()->asSiswa()->create(['name' => 'Siti Rahayu']);

    $policy = AccessPolicy::factory()->create();
    $grantedBy = User::factory()->asAdmin()->create();

    $targetLog = TemporaryAccessLog::create([
        'user_id' => $targetUser->id,
        'access_policy_id' => $policy->id,
        'ability' => 'viewAny',
        'granted_by_user_id' => $grantedBy->id,
        'granted_at' => now(),
        'expires_at' => now()->addWeek(),
    ]);

    $otherLog = TemporaryAccessLog::create([
        'user_id' => $otherUser->id,
        'access_policy_id' => $policy->id,
        'ability' => 'create',
        'granted_by_user_id' => $grantedBy->id,
        'granted_at' => now(),
        'expires_at' => now()->addWeek(),
    ]);

    Livewire::test(TemporaryAccessLogList::class)
        ->searchTable('Budi Santoso')
        ->assertCanSeeTableRecords([$targetLog])
        ->assertCanNotSeeTableRecords([$otherLog]);
});

// ===========================================================================
// Requirement 3.8 — Filter status "Dicabut"
// ===========================================================================

test('filter status Dicabut hanya menampilkan log dengan revoked_at tidak null', function () {
    $revokedBy = User::factory()->asAdmin()->create();

    $activeLog = makeLog(['revoked_at' => null]);
    $revokedLog = makeLog([
        'revoked_at' => now()->subHour(),
        'revoked_by_user_id' => $revokedBy->id,
    ]);

    Livewire::test(TemporaryAccessLogList::class)
        ->filterTable('status', 'revoked')
        ->assertCanSeeTableRecords([$revokedLog])
        ->assertCanNotSeeTableRecords([$activeLog]);
});

// ===========================================================================
// Requirement 3.10 — Default sort adalah granted_at descending
// ===========================================================================

test('default sort adalah granted_at descending', function () {
    $policy = AccessPolicy::factory()->create();
    $user = User::factory()->asSiswa()->create();
    $grantedBy = User::factory()->asAdmin()->create();

    $oldest = TemporaryAccessLog::create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'ability' => 'viewAny',
        'granted_by_user_id' => $grantedBy->id,
        'granted_at' => now()->subDays(3),
        'expires_at' => now()->addWeek(),
    ]);

    $middle = TemporaryAccessLog::create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'ability' => 'create',
        'granted_by_user_id' => $grantedBy->id,
        'granted_at' => now()->subDay(),
        'expires_at' => now()->addWeek(),
    ]);

    $newest = TemporaryAccessLog::create([
        'user_id' => $user->id,
        'access_policy_id' => $policy->id,
        'ability' => 'update',
        'granted_by_user_id' => $grantedBy->id,
        'granted_at' => now(),
        'expires_at' => now()->addWeek(),
    ]);

    $expectedOrder = TemporaryAccessLog::query()
        ->orderBy('granted_at', 'desc')
        ->get();

    Livewire::test(TemporaryAccessLogList::class)
        ->assertCanSeeTableRecords($expectedOrder, inOrder: true);
});
