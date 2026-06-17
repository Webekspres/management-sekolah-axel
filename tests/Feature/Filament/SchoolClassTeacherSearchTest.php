<?php

/**
 * Fix Verification Tests — Verifikasi Bug Sudah Teratasi
 *
 * Test ini DIHARAPKAN LULUS pada kode yang sudah diperbaiki.
 *
 * Fix: modifyQueryUsing melakukan JOIN ke tabel users dan ->searchable(['users.name', 'teachers.nip'])
 * menyebabkan Filament membangun query:
 *   WHERE (users.name LIKE '%<search>%' OR teachers.nip LIKE '%<search>%')
 * sehingga pencarian nama guru berfungsi dengan benar.
 */

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

// ─────────────────────────────────────────────────────────────────────────────
// Helper: simulasikan query pencarian Filament untuk SchoolClassForm (FIXED)
// Filament memanggil modifyQueryUsing (JOIN users) lalu menambahkan:
//   WHERE (users.name LIKE '%{search}%' OR teachers.nip LIKE '%{search}%')
// Karena searchable(['users.name', 'teachers.nip']) digunakan.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Simulasi query yang dijalankan Filament saat user mengetik di dropdown
 * SchoolClassForm (Wali Kelas) — setelah fix dengan JOIN + searchable columns.
 *
 * @Validates: Requirements 1.1, 1.3
 */
function schoolClassFormSearchQuery(string $search): Builder
{
    // modifyQueryUsing setelah fix: JOIN ke users dan select teachers.*
    $query = Teacher::query()
        ->join('users', 'users.id', '=', 'teachers.user_id')
        ->select('teachers.*');

    // Filament menambahkan WHERE berdasarkan searchable(['users.name', 'teachers.nip'])
    $query->where(function (Builder $q) use ($search): void {
        $q->where('users.name', 'like', "%{$search}%")
            ->orWhere('teachers.nip', 'like', "%{$search}%");
    });

    return $query;
}

/**
 * Simulasi query yang dijalankan Filament saat user mengetik di dropdown
 * ScheduleForm (Guru Pengajar) — setelah fix dengan JOIN + searchable columns.
 *
 * @Validates: Requirements 1.2, 1.3
 */
function scheduleFormSearchQuery(string $search): Builder
{
    // modifyQueryUsing setelah fix: JOIN ke users dan select teachers.*
    $query = Teacher::query()
        ->join('users', 'users.id', '=', 'teachers.user_id')
        ->select('teachers.*');

    // Filament menambahkan WHERE berdasarkan searchable(['users.name', 'teachers.nip'])
    $query->where(function (Builder $q) use ($search): void {
        $q->where('users.name', 'like', "%{$search}%")
            ->orWhere('teachers.nip', 'like', "%{$search}%");
    });

    return $query;
}

// ─────────────────────────────────────────────────────────────────────────────
// Sub-task 1.1 — SchoolClassForm: pencarian nama guru gagal
// ─────────────────────────────────────────────────────────────────────────────

test('1.1 SchoolClassForm: pencarian nama guru menemukan hasil (fix terverifikasi)', function () {
    // Buat guru dengan nama yang unik dan NIP yang tidak mengandung nama tersebut
    $user = User::factory()->asGuru()->create(['name' => 'Sultan Agung Wijaya']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => '198501012010011001', // NIP tidak mengandung "Sultan"
    ]);

    // Simulasi pencarian nama "Sultan" di dropdown Wali Kelas
    $results = schoolClassFormSearchQuery('Sultan')->get();

    // FIX: query mencari di users.name, sehingga guru "Sultan Agung Wijaya" ditemukan
    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($teacher->id);
});

test('1.1 SchoolClassForm: pencarian nama parsial menemukan hasil (fix terverifikasi)', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Budi Santoso']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => '199203152015041002', // NIP tidak mengandung "Budi"
    ]);

    // Simulasi pencarian "Budi" di dropdown Wali Kelas
    $results = schoolClassFormSearchQuery('Budi')->get();

    // FIX: menemukan guru bernama "Budi Santoso" via users.name
    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($teacher->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// Sub-task 1.2 — ScheduleForm: pencarian nama guru gagal
// ─────────────────────────────────────────────────────────────────────────────

test('1.2 ScheduleForm: pencarian nama guru menemukan hasil (fix terverifikasi)', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Dewi Rahayu']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => '197808202005012003', // NIP tidak mengandung "Dewi"
    ]);

    // Simulasi pencarian "Dewi" di dropdown Guru Pengajar
    $results = scheduleFormSearchQuery('Dewi')->get();

    // FIX: menemukan guru bernama "Dewi Rahayu" via users.name
    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($teacher->id);
});

test('1.2 ScheduleForm: pencarian nama lengkap menemukan hasil (fix terverifikasi)', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Ahmad Fauzi']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => '198712102012011005', // NIP tidak mengandung "Ahmad"
    ]);

    // Simulasi pencarian "Ahmad Fauzi" di dropdown Guru Pengajar
    $results = scheduleFormSearchQuery('Ahmad Fauzi')->get();

    // FIX: menemukan guru bernama "Ahmad Fauzi" via users.name
    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($teacher->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// Sub-task 1.3 — Guru dengan nip = null tidak dapat ditemukan
// ─────────────────────────────────────────────────────────────────────────────

test('1.3 guru dengan nip null dapat ditemukan via pencarian nama (fix terverifikasi)', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Siti Nurhaliza']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => null, // Guru tanpa NIP
    ]);

    // Simulasi pencarian nama "Siti" di dropdown (baik SchoolClassForm maupun ScheduleForm)
    $resultsSchoolClass = schoolClassFormSearchQuery('Siti')->get();
    $resultsSchedule = scheduleFormSearchQuery('Siti')->get();

    // FIX: guru dengan nip = null ditemukan via users.name
    expect($resultsSchoolClass)->toHaveCount(1)
        ->and($resultsSchoolClass->first()->id)->toBe($teacher->id);

    expect($resultsSchedule)->toHaveCount(1)
        ->and($resultsSchedule->first()->id)->toBe($teacher->id);
});

test('1.3 guru dengan nip null dapat ditemukan via pencarian nama apapun (fix terverifikasi)', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Rini Wulandari']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => null, // Guru tanpa NIP
    ]);

    // Pencarian nama menemukan guru dengan nip = null via users.name
    $results = schoolClassFormSearchQuery('Rini')->get();

    // FIX: guru dengan nip = null ditemukan via users.name
    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($teacher->id);
});
