<?php

/**
 * Verifikasi perilaku keamanan pengumuman di panel siswa setelah perbaikan bug.
 */

use App\Filament\Student\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Student\Resources\Announcements\Pages\ViewAnnouncement;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('student'));
});

test('siswa tidak memiliki DeleteBulkAction di tabel pengumuman', function () {
    $siswa = User::factory()->asSiswa()->create();
    Announcement::factory()->forSiswa()->create();

    actingAs($siswa);

    Livewire::test(ListAnnouncements::class)
        ->assertActionDoesNotExist(TestAction::make(DeleteBulkAction::class)->table()->bulk());
});

test('siswa tidak memiliki EditAction di baris tabel pengumuman', function () {
    $siswa = User::factory()->asSiswa()->create();
    $announcement = Announcement::factory()->forSiswa()->create();

    actingAs($siswa);

    Livewire::test(ListAnnouncements::class)
        ->assertTableActionDoesNotExist('edit', record: $announcement);
});

test('siswa tidak memiliki CreateAction di header ListAnnouncements', function () {
    $siswa = User::factory()->asSiswa()->create();

    actingAs($siswa);

    Livewire::test(ListAnnouncements::class)
        ->assertActionDoesNotExist(CreateAction::class);
});

test('siswa tidak memiliki EditAction di header ViewAnnouncement', function () {
    $siswa = User::factory()->asSiswa()->create();
    $announcement = Announcement::factory()->forSiswa()->create();

    actingAs($siswa);

    Livewire::test(ViewAnnouncement::class, ['record' => $announcement->id])
        ->assertActionDoesNotExist(EditAction::class);
});

test('status baca tercatat saat ViewAnnouncement dibuka', function () {
    $siswa = User::factory()->asSiswa()->create();
    $announcement = Announcement::factory()->forSiswa()->create();

    actingAs($siswa);

    Livewire::test(ViewAnnouncement::class, ['record' => $announcement->id]);

    expect(AnnouncementRead::query()
        ->where('announcement_id', $announcement->id)
        ->where('user_id', $siswa->id)
        ->exists())->toBeTrue();
});
