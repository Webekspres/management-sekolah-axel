<?php

use App\Filament\Widgets\AdminOverviewStats;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('admin overview stats menampilkan ringkasan fase 2', function () {
    $admin = User::factory()->asAdmin()->create();

    Student::factory()->count(2)->create();
    Teacher::factory()->count(3)->create();

    $level = Level::factory()->create();
    $homeroomTeacher = Teacher::factory()->create();

    $activeYear = AcademicYear::factory()->active()->create();
    $inactiveYear = AcademicYear::factory()->create();

    SchoolClass::factory()->create([
        'level_id' => $level->id,
        'teacher_id' => $homeroomTeacher->id,
        'academic_year_id' => $activeYear->id,
    ]);

    SchoolClass::factory()->create([
        'level_id' => $level->id,
        'teacher_id' => $homeroomTeacher->id,
        'academic_year_id' => $inactiveYear->id,
    ]);

    $kbmToday = Kbm::factory()->create([
        'date' => today()->toDateString(),
    ]);

    Attendance::factory()->hadir()->create(['kbm_id' => $kbmToday->id]);
    Attendance::factory()->izin()->create(['kbm_id' => $kbmToday->id]);
    Attendance::factory()->sakit()->create(['kbm_id' => $kbmToday->id]);

    $kbmYesterday = Kbm::factory()->create([
        'date' => today()->subDay()->toDateString(),
    ]);
    Attendance::factory()->alpa()->create(['kbm_id' => $kbmYesterday->id]);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(AdminOverviewStats::class)
        ->assertSee('Total Siswa')
        ->assertSee('Total Guru dan Staf')
        ->assertSee('Kelas Aktif')
        ->assertSee('Kehadiran Hari Ini')
        ->assertSee('2')
        ->assertSee('3')
        ->assertSee('1')
        ->assertSee('H: 1 | I: 1 | S: 1 | A: 0');
});
