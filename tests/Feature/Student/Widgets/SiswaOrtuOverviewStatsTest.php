<?php

use App\Filament\Student\Widgets\SiswaOrtuOverviewStats;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Schedule;
use App\Models\Student;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('widget siswa menampilkan jadwal hari ini, pengumuman terbaru, dan ringkasan kehadiran sendiri', function () {
    $student = Student::factory()->create();

    Schedule::factory()->create([
        'class_id' => $student->class_id,
        'day_of_week' => now()->dayOfWeekIso,
    ]);

    $kbmApproved = Kbm::factory()->create([
        'date' => today()->toDateString(),
        'status' => 'APPROVED',
    ]);

    $kbmPending = Kbm::factory()->create([
        'date' => today()->toDateString(),
        'status' => 'PENDING',
    ]);

    Attendance::factory()->hadir()->create([
        'kbm_id' => $kbmApproved->id,
        'student_id' => $student->id,
    ]);

    Attendance::factory()->alpa()->create([
        'kbm_id' => $kbmPending->id,
        'student_id' => $student->id,
    ]);

    Announcement::factory()->create([
        'title' => 'Pengumuman Lama Siswa',
        'target_role' => ['siswa_ortu'],
        'created_at' => now()->subDay(),
    ]);

    Announcement::factory()->create([
        'title' => 'Pengumuman Terbaru Siswa',
        'target_role' => ['siswa_ortu'],
        'created_at' => now(),
    ]);

    $this->actingAs($student->user);
    Filament::setCurrentPanel(Filament::getPanel('student'));

    Livewire::test(SiswaOrtuOverviewStats::class)
        ->assertSee('Jadwal Hari Ini')
        ->assertSee('Pengumuman Terbaru')
        ->assertSee('Pengumuman Terbaru Siswa')
        ->assertSee('Ringkasan Kehadiran Saya')
        ->assertSee('1')
        ->assertSee('Bulan ini (APPROVED), HADIR: 1');
});
