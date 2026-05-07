<?php

use App\Filament\Pages\ActivityLogPage;
use App\Models\ActivityLog;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->admin = User::factory()->asAdmin()->create();
    $this->actingAs($this->admin);
});

// ---------------------------------------------------------------------------
// Helper: buat ActivityLog langsung tanpa factory
// ---------------------------------------------------------------------------

/**
 * @param  array<string, mixed>  $overrides
 */
function makeActivityLog(array $overrides = []): ActivityLog
{
    return ActivityLog::create(array_merge([
        'user_id' => null,
        'action' => 'created',
        'entity_type' => 'App\\Models\\User',
        'entity_id' => null,
        'log_name' => 'general',
        'description' => 'Test activity log entry',
        'properties' => null,
    ], $overrides));
}

// ---------------------------------------------------------------------------
// 1. Tabel menampilkan records
// ---------------------------------------------------------------------------

test('tabel menampilkan ActivityLog records', function () {
    makeActivityLog(['description' => 'Log entry 1']);
    makeActivityLog(['description' => 'Log entry 2']);
    makeActivityLog(['description' => 'Log entry 3']);

    Livewire::test(ActivityLogPage::class)
        ->assertCountTableRecords(3);
});

// ---------------------------------------------------------------------------
// 2. Filter user_id
// ---------------------------------------------------------------------------

test('filter user_id mengembalikan hanya record user tersebut', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $logA = makeActivityLog(['user_id' => $userA->id, 'description' => 'Log user A']);
    $logB = makeActivityLog(['user_id' => $userB->id, 'description' => 'Log user B']);

    Livewire::test(ActivityLogPage::class)
        ->filterTable('user_id', $userA->id)
        ->assertCanSeeTableRecords([$logA])
        ->assertCanNotSeeTableRecords([$logB]);
});

// ---------------------------------------------------------------------------
// 3. Filter action
// ---------------------------------------------------------------------------

test('filter action mengembalikan hanya record dengan action tersebut', function () {
    $logCreated = makeActivityLog(['action' => 'created', 'description' => 'Created entry']);
    $logDeleted = makeActivityLog(['action' => 'deleted', 'description' => 'Deleted entry']);

    Livewire::test(ActivityLogPage::class)
        ->filterTable('action', 'created')
        ->assertCanSeeTableRecords([$logCreated])
        ->assertCanNotSeeTableRecords([$logDeleted]);
});

// ---------------------------------------------------------------------------
// 4. Filter log_name
// ---------------------------------------------------------------------------

test('filter log_name mengembalikan hanya record modul tersebut', function () {
    $logAuth = makeActivityLog(['log_name' => 'auth', 'action' => 'login', 'description' => 'Auth log']);
    $logSpp = makeActivityLog(['log_name' => 'spp', 'action' => 'created', 'description' => 'SPP log']);

    Livewire::test(ActivityLogPage::class)
        ->filterTable('log_name', 'auth')
        ->assertCanSeeTableRecords([$logAuth])
        ->assertCanNotSeeTableRecords([$logSpp]);
});

// ---------------------------------------------------------------------------
// 5. Read-only — tidak ada action create/edit/delete
// ---------------------------------------------------------------------------

test('tabel tidak memiliki action create', function () {
    Livewire::test(ActivityLogPage::class)
        ->assertActionDoesNotExist('create');
});

test('tabel tidak memiliki action edit pada record', function () {
    $log = makeActivityLog();

    Livewire::test(ActivityLogPage::class)
        ->assertTableActionDoesNotExist('edit', record: $log);
});

test('tabel tidak memiliki action delete pada record', function () {
    $log = makeActivityLog();

    Livewire::test(ActivityLogPage::class)
        ->assertTableActionDoesNotExist('delete', record: $log);
});

// ---------------------------------------------------------------------------
// 6. Search pada description
// ---------------------------------------------------------------------------

test('search pada description menemukan record yang sesuai', function () {
    $logMatch = makeActivityLog(['description' => 'User melakukan login ke sistem']);
    $logNoMatch = makeActivityLog(['description' => 'Data absensi diperbarui']);

    Livewire::test(ActivityLogPage::class)
        ->searchTable('login ke sistem')
        ->assertCanSeeTableRecords([$logMatch])
        ->assertCanNotSeeTableRecords([$logNoMatch]);
});
