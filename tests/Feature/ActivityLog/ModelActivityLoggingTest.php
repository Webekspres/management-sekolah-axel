<?php

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

// ---------------------------------------------------------------------------
// 1. Create model → ActivityLog dibuat
// ---------------------------------------------------------------------------

test('create model dengan trait menghasilkan ActivityLog dengan action created', function () {
    $user = User::factory()->asAdmin()->create();
    $this->actingAs($user);

    $attendance = Attendance::factory()->create();

    assertDatabaseHas(ActivityLog::class, [
        'action' => 'created',
        'entity_type' => Attendance::class,
        'entity_id' => $attendance->getKey(),
        'user_id' => $user->id,
    ]);
});

test('create model mencatat entity_type yang benar', function () {
    $user = User::factory()->asAdmin()->create();
    $this->actingAs($user);

    $schedule = Schedule::factory()->create();

    assertDatabaseHas(ActivityLog::class, [
        'action' => 'created',
        'entity_type' => Schedule::class,
        'entity_id' => $schedule->getKey(),
    ]);
});

// ---------------------------------------------------------------------------
// 2. Update model → ActivityLog dengan action updated + properties old/new
// ---------------------------------------------------------------------------

test('update model menghasilkan ActivityLog dengan action updated', function () {
    $user = User::factory()->asAdmin()->create();
    $this->actingAs($user);

    $attendance = Attendance::factory()->hadir()->create();
    $attendance->update(['status' => 'SAKIT']);

    assertDatabaseHas(ActivityLog::class, [
        'action' => 'updated',
        'entity_type' => Attendance::class,
        'entity_id' => $attendance->getKey(),
        'user_id' => $user->id,
    ]);
});

test('update model menyimpan old dan new values di properties', function () {
    $user = User::factory()->asAdmin()->create();
    $this->actingAs($user);

    $attendance = Attendance::factory()->hadir()->create();
    $attendance->update(['status' => 'SAKIT']);

    $log = ActivityLog::where('action', 'updated')
        ->where('entity_type', Attendance::class)
        ->where('entity_id', $attendance->getKey())
        ->latest('created_at')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->properties)->toHaveKey('old');
    expect($log->properties)->toHaveKey('new');
    expect($log->properties['old']['status'])->toBe('HADIR');
    expect($log->properties['new']['status'])->toBe('SAKIT');
});

// ---------------------------------------------------------------------------
// 3. Delete model → ActivityLog dengan action deleted
// ---------------------------------------------------------------------------

test('delete model menghasilkan ActivityLog dengan action deleted', function () {
    $user = User::factory()->asAdmin()->create();
    $this->actingAs($user);

    $attendance = Attendance::factory()->create();
    $attendanceId = $attendance->getKey();
    $attendance->delete();

    assertDatabaseHas(ActivityLog::class, [
        'action' => 'deleted',
        'entity_type' => Attendance::class,
        'entity_id' => $attendanceId,
        'user_id' => $user->id,
    ]);
});

// ---------------------------------------------------------------------------
// 4. Tanpa auth → user_id = null
// ---------------------------------------------------------------------------

test('create model tanpa auth menghasilkan ActivityLog dengan user_id null', function () {
    // Pastikan tidak ada user yang terautentikasi
    auth()->logout();

    $attendance = Attendance::factory()->create();

    assertDatabaseHas(ActivityLog::class, [
        'action' => 'created',
        'entity_type' => Attendance::class,
        'entity_id' => $attendance->getKey(),
        'user_id' => null,
    ]);
});

// ---------------------------------------------------------------------------
// 5. log_name sesuai model
// ---------------------------------------------------------------------------

test('ActivityLog mencatat log_name yang benar untuk Attendance', function () {
    $user = User::factory()->asAdmin()->create();
    $this->actingAs($user);

    $attendance = Attendance::factory()->create();

    assertDatabaseHas(ActivityLog::class, [
        'action' => 'created',
        'entity_type' => Attendance::class,
        'entity_id' => $attendance->getKey(),
        'log_name' => 'absensi',
    ]);
});

test('ActivityLog mencatat log_name yang benar untuk Schedule', function () {
    $user = User::factory()->asAdmin()->create();
    $this->actingAs($user);

    $schedule = Schedule::factory()->create();

    assertDatabaseHas(ActivityLog::class, [
        'action' => 'created',
        'entity_type' => Schedule::class,
        'entity_id' => $schedule->getKey(),
        'log_name' => 'jadwal',
    ]);
});
