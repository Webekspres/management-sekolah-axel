<?php

use App\Filament\Widgets\KepsekOverviewStats;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('kepsek overview stats menampilkan kehadiran, kbm, dan pengumuman terbaru', function () {
    $kepsek = User::factory()->asKepalaSekolah()->create();

    $kbmTodayApproved = Kbm::factory()->create([
        'date' => today()->toDateString(),
        'status' => 'APPROVED',
    ]);

    Kbm::factory()->create([
        'date' => today()->toDateString(),
        'status' => 'PENDING',
    ]);

    Kbm::factory()->create([
        'date' => today()->subDay()->toDateString(),
        'status' => 'PENDING',
    ]);

    Attendance::factory()->hadir()->create(['kbm_id' => $kbmTodayApproved->id]);
    Attendance::factory()->alpa()->create(['kbm_id' => $kbmTodayApproved->id]);

    Announcement::factory()->create([
        'title' => 'Pengumuman Lama Kepsek',
        'target_role' => ['kepala_sekolah'],
        'created_at' => now()->subDay(),
    ]);

    Announcement::factory()->create([
        'title' => 'Pengumuman Terbaru Kepsek',
        'target_role' => ['kepala_sekolah'],
        'created_at' => now(),
    ]);

    $this->actingAs($kepsek);
    Filament::setCurrentPanel(Filament::getPanel('kepsek'));

    Livewire::test(KepsekOverviewStats::class)
        ->assertSee('Overview Kehadiran')
        ->assertSee('2')
        ->assertSee('Overview KBM')
        ->assertSee('KBM hari ini, pending: 2')
        ->assertSee('Pengumuman Terbaru')
        ->assertSee('Pengumuman Terbaru Kepsek');
});
