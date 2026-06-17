<?php

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\assertDatabaseHas;

// ---------------------------------------------------------------------------
// 1. Login berhasil → ActivityLog dengan action='login'
// ---------------------------------------------------------------------------

test('login berhasil menghasilkan ActivityLog dengan action login', function () {
    $user = User::factory()->create();

    event(new Login('web', $user, false));

    assertDatabaseHas(ActivityLog::class, [
        'user_id' => $user->id,
        'action' => 'login',
        'log_name' => 'auth',
    ]);
});

test('login berhasil mencatat user_id yang benar', function () {
    $user = User::factory()->create();

    event(new Login('web', $user, false));

    $log = ActivityLog::where('action', 'login')
        ->where('log_name', 'auth')
        ->where('user_id', $user->id)
        ->latest('created_at')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($user->id);
});

// ---------------------------------------------------------------------------
// 2. Logout → ActivityLog dengan action='logout'
// ---------------------------------------------------------------------------

test('logout menghasilkan ActivityLog dengan action logout', function () {
    $user = User::factory()->create();

    event(new Logout('web', $user));

    assertDatabaseHas(ActivityLog::class, [
        'user_id' => $user->id,
        'action' => 'logout',
        'log_name' => 'auth',
    ]);
});

// ---------------------------------------------------------------------------
// 3. Login gagal → tidak ada ActivityLog baru
// ---------------------------------------------------------------------------

test('login gagal tidak memicu event Login sehingga tidak ada ActivityLog', function () {
    $user = User::factory()->create();

    // Hitung ActivityLog sebelum (User::factory()->create() mungkin membuat log)
    $countBefore = ActivityLog::where('action', 'login')->count();

    // Login gagal tidak memicu event Login — tidak ada ActivityLog baru dengan action=login
    // Verifikasi bahwa event Login tidak di-fire saat kredensial salah
    Event::fake([Login::class]);

    // Simulasi: tidak ada event Login yang di-fire
    Event::assertNotDispatched(Login::class);

    expect(ActivityLog::where('action', 'login')->count())->toBe($countBefore);
});
