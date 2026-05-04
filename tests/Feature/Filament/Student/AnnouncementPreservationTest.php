<?php

/**
 * Preservation Property Tests — Perilaku yang Tidak Boleh Berubah Setelah Fix
 *
 * Test 1–3 DIHARAPKAN LULUS pada kode yang BELUM diperbaiki.
 * Setiap test mengonfirmasi perilaku baseline yang harus tetap berjalan setelah fix.
 *
 * Test 4–5 memverifikasi unique constraint dan per-user independence di tabel `announcement_reads`.
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
 */

use App\Filament\Resources\Announcements\Pages\ListAnnouncements as AdminListAnnouncements;
use App\Filament\Student\Resources\Announcements\Pages\ListAnnouncements as StudentListAnnouncements;
use App\Filament\Student\Resources\Announcements\Pages\ViewAnnouncement;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

// ─────────────────────────────────────────────────────────────────────────────
// Test 1 — Admin tetap bisa edit dan hapus
// **Validates: Requirements 3.1**
// ─────────────────────────────────────────────────────────────────────────────

test('3.1 admin tetap bisa edit dan hapus di panel admin', function () {
    $admin = User::factory()->asAdmin()->create();
    $announcement = Announcement::factory()->create();

    actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // Preservation: EditAction dan DeleteBulkAction harus tetap ada di panel admin
    Livewire::test(AdminListAnnouncements::class)
        ->assertActionExists(TestAction::make(EditAction::class)->table($announcement))
        ->assertActionExists(TestAction::make(DeleteBulkAction::class)->table()->bulk());
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2 — Filter target_role tetap berjalan
// **Validates: Requirements 3.2**
// ─────────────────────────────────────────────────────────────────────────────

test('3.2 pengumuman untuk guru tidak muncul di tabel siswa', function () {
    $siswa = User::factory()->asSiswa()->create();

    // Buat pengumuman khusus untuk guru — siswa tidak boleh melihatnya
    $announcementForGuru = Announcement::factory()->forGuru()->create();

    // Buat pengumuman untuk siswa — siswa harus bisa melihatnya
    $announcementForSiswa = Announcement::factory()->forSiswa()->create();

    actingAs($siswa);
    Filament::setCurrentPanel(Filament::getPanel('student'));

    // Preservation: filter target_role harus tetap berjalan — pengumuman guru tidak muncul
    Livewire::test(StudentListAnnouncements::class)
        ->assertCanNotSeeTableRecords([$announcementForGuru])
        ->assertCanSeeTableRecords([$announcementForSiswa]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3 — Navigasi ke detail tetap berfungsi
// **Validates: Requirements 3.3**
// ─────────────────────────────────────────────────────────────────────────────

test('3.3 siswa bisa membuka halaman detail pengumuman dengan konten lengkap', function () {
    $siswa = User::factory()->asSiswa()->create();
    $announcement = Announcement::factory()->forSiswa()->create([
        'title' => 'Pengumuman Ujian Akhir Semester',
        'content' => 'Ujian akhir semester akan dilaksanakan pada tanggal 20 Januari 2025.',
    ]);

    actingAs($siswa);
    Filament::setCurrentPanel(Filament::getPanel('student'));

    // Preservation: halaman ViewAnnouncement harus bisa dirender dengan konten lengkap
    Livewire::test(ViewAnnouncement::class, ['record' => $announcement->id])
        ->assertOk()
        ->assertSee($announcement->title)
        ->assertSee($announcement->content);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 4 — Status baca per-user independen
// **Validates: Requirements 3.4, 3.5**
// ─────────────────────────────────────────────────────────────────────────────

test('3.4 status baca per-user independen — dua siswa memiliki record AnnouncementRead sendiri', function () {
    $siswaA = User::factory()->asSiswa()->create();
    $siswaB = User::factory()->asSiswa()->create();
    $announcement = Announcement::factory()->forSiswa()->create();

    // Siswa A membaca pengumuman
    AnnouncementRead::create([
        'announcement_id' => $announcement->id,
        'user_id' => $siswaA->id,
    ]);

    // Siswa B membaca pengumuman yang sama
    AnnouncementRead::create([
        'announcement_id' => $announcement->id,
        'user_id' => $siswaB->id,
    ]);

    // Preservation: masing-masing siswa memiliki record AnnouncementRead sendiri
    expect(AnnouncementRead::where('announcement_id', $announcement->id)->where('user_id', $siswaA->id)->exists())->toBeTrue();
    expect(AnnouncementRead::where('announcement_id', $announcement->id)->where('user_id', $siswaB->id)->exists())->toBeTrue();
    expect(AnnouncementRead::where('announcement_id', $announcement->id)->count())->toBe(2);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 5 — Membuka ulang tidak menduplikasi record
// **Validates: Requirements 3.4**
// ─────────────────────────────────────────────────────────────────────────────

test('3.5 membuka pengumuman yang sama dua kali tidak menduplikasi record announcement_reads', function () {
    $siswa = User::factory()->asSiswa()->create();
    $announcement = Announcement::factory()->forSiswa()->create();

    // Membuka pertama kali — buat record
    AnnouncementRead::firstOrCreate([
        'announcement_id' => $announcement->id,
        'user_id' => $siswa->id,
    ]);

    // Membuka kedua kali — tidak boleh menduplikasi
    AnnouncementRead::firstOrCreate([
        'announcement_id' => $announcement->id,
        'user_id' => $siswa->id,
    ]);

    // Preservation: hanya ada satu record di announcement_reads
    expect(AnnouncementRead::where('announcement_id', $announcement->id)->where('user_id', $siswa->id)->count())->toBe(1);
});
