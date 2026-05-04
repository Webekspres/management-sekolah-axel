<?php

/**
 * Bug Condition Exploration Tests — Konfirmasi Bug Ada di Kode yang Belum Diperbaiki
 *
 * Test ini DIHARAPKAN LULUS pada kode yang BELUM diperbaiki.
 * Setiap test mengonfirmasi keberadaan bug dengan meng-assert perilaku buggy saat ini.
 *
 * Setelah fix diimplementasikan, test-test ini akan GAGAL — yang berarti bug sudah teratasi.
 *
 * **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**
 */

use App\Filament\Student\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Student\Resources\Announcements\Pages\ViewAnnouncement;
use App\Models\Announcement;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('student'));
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 1 — DeleteBulkAction ada di tabel siswa (BUG: seharusnya tidak ada)
// **Validates: Requirements 1.1**
// ─────────────────────────────────────────────────────────────────────────────

test('1.1 DeleteBulkAction ada di tabel siswa (bug terkonfirmasi)', function () {
    $siswa = User::factory()->asSiswa()->create();
    Announcement::factory()->forSiswa()->create();

    actingAs($siswa);

    // BUG: DeleteBulkAction seharusnya tidak ada di panel siswa,
    // tapi saat ini masih ada karena AnnouncementsTable menyertakannya.
    Livewire::test(ListAnnouncements::class)
        ->assertActionExists(TestAction::make(DeleteBulkAction::class)->table()->bulk());
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2 — EditAction ada di baris tabel siswa (BUG: seharusnya tidak ada)
// **Validates: Requirements 1.2**
// ─────────────────────────────────────────────────────────────────────────────

test('1.2 EditAction ada di baris tabel siswa (bug terkonfirmasi)', function () {
    $siswa = User::factory()->asSiswa()->create();
    $announcement = Announcement::factory()->forSiswa()->create();

    actingAs($siswa);

    // BUG: EditAction seharusnya tidak ada di record actions panel siswa,
    // tapi saat ini masih ada karena AnnouncementsTable menyertakannya.
    Livewire::test(ListAnnouncements::class)
        ->assertActionExists(TestAction::make(EditAction::class)->table($announcement));
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3 — CreateAction ada di header ListAnnouncements (BUG: seharusnya tidak ada)
// **Validates: Requirements 1.3**
// ─────────────────────────────────────────────────────────────────────────────

test('1.3 CreateAction ada di header ListAnnouncements (bug terkonfirmasi)', function () {
    $siswa = User::factory()->asSiswa()->create();

    actingAs($siswa);

    // BUG: CreateAction seharusnya tidak ada di header ListAnnouncements panel siswa,
    // tapi saat ini masih ada karena ListAnnouncements::getHeaderActions() mengembalikannya.
    Livewire::test(ListAnnouncements::class)
        ->assertActionExists(CreateAction::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 4 — EditAction ada di header ViewAnnouncement (BUG: seharusnya tidak ada)
// **Validates: Requirements 1.4**
// ─────────────────────────────────────────────────────────────────────────────

test('1.4 EditAction ada di header ViewAnnouncement (bug terkonfirmasi)', function () {
    $siswa = User::factory()->asSiswa()->create();
    $announcement = Announcement::factory()->forSiswa()->create();

    actingAs($siswa);

    // BUG: EditAction seharusnya tidak ada di header ViewAnnouncement panel siswa,
    // tapi saat ini masih ada karena ViewAnnouncement::getHeaderActions() mengembalikannya.
    Livewire::test(ViewAnnouncement::class, ['record' => $announcement->id])
        ->assertActionExists(EditAction::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 5 — Status baca tidak tercatat saat ViewAnnouncement dibuka (BUG)
// **Validates: Requirements 1.5**
// ─────────────────────────────────────────────────────────────────────────────

test('1.5 status baca tidak tercatat saat ViewAnnouncement dibuka (bug terkonfirmasi)', function () {
    $siswa = User::factory()->asSiswa()->create();
    $announcement = Announcement::factory()->forSiswa()->create();

    actingAs($siswa);

    // Buka halaman detail pengumuman sebagai siswa
    Livewire::test(ViewAnnouncement::class, ['record' => $announcement->id]);

    // BUG: Tidak ada record di announcement_reads setelah membuka ViewAnnouncement,
    // karena tabel announcement_reads belum ada dan ViewAnnouncement::mount()
    // tidak mencatat status baca.
    //
    // Konfirmasi bug: tabel announcement_reads tidak ada di database.
    expect(Schema::hasTable('announcement_reads'))->toBeFalse();
});
