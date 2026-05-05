<?php

use App\Filament\Guru\Widgets\GuruTodayChecklistTable;
use App\Filament\Widgets\GuruOverviewStats;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('guru overview stats menampilkan jadwal, kbm terbaru, dan ringkasan absensi kelas', function () {
    $teacher = Teacher::factory()->create();

    $schoolClass = SchoolClass::factory()->create([
        'teacher_id' => $teacher->id,
    ]);

    $scheduleToday = Schedule::factory()->create([
        'teacher_id' => $teacher->id,
        'class_id' => $schoolClass->id,
        'day_of_week' => now()->dayOfWeekIso,
    ]);

    Schedule::factory()->create([
        'day_of_week' => now()->dayOfWeekIso,
    ]);

    Kbm::factory()->create([
        'schedule_id' => $scheduleToday->id,
        'date' => today()->subDay()->toDateString(),
        'status' => 'APPROVED',
    ]);

    $latestKbm = Kbm::factory()->create([
        'schedule_id' => $scheduleToday->id,
        'date' => today()->toDateString(),
        'status' => 'PENDING',
    ]);

    $students = Student::factory()->count(3)->create([
        'class_id' => $schoolClass->id,
    ]);

    Attendance::factory()->hadir()->create([
        'kbm_id' => $latestKbm->id,
        'student_id' => $students[0]->id,
    ]);
    Attendance::factory()->izin()->create([
        'kbm_id' => $latestKbm->id,
        'student_id' => $students[1]->id,
    ]);
    Attendance::factory()->hadir()->create([
        'kbm_id' => $latestKbm->id,
        'student_id' => $students[2]->id,
    ]);

    $this->actingAs($teacher->user);
    Filament::setCurrentPanel(Filament::getPanel('guru'));

    Livewire::test(GuruOverviewStats::class)
        ->assertSee('Jadwal Hari Ini')
        ->assertSee('KBM Terbaru')
        ->assertSee('PENDING')
        ->assertSee('Ringkasan Absensi Kelas')
        ->assertSee('3')
        ->assertSee('(67%)')
        ->assertSee('RPP saya (draft)');

    Livewire::test(GuruTodayChecklistTable::class)->assertSuccessful();
});
