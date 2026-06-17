<?php

use App\Filament\Student\Widgets\StudentAttendanceSummaryWidget;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('widget menampilkan jumlah HADIR SAKIT IZIN ALPA yang benar untuk siswa yang login', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user);
    Filament::setCurrentPanel(Filament::getPanel('student'));

    $kbm1 = Kbm::factory()->create();
    $kbm2 = Kbm::factory()->create();
    $kbm3 = Kbm::factory()->create();
    $kbm4 = Kbm::factory()->create();

    Attendance::factory()->hadir()->create(['student_id' => $student->id, 'kbm_id' => $kbm1->id]);
    Attendance::factory()->sakit()->create(['student_id' => $student->id, 'kbm_id' => $kbm2->id]);
    Attendance::factory()->izin()->create(['student_id' => $student->id, 'kbm_id' => $kbm3->id]);
    Attendance::factory()->alpa()->create(['student_id' => $student->id, 'kbm_id' => $kbm4->id]);

    Livewire::test(StudentAttendanceSummaryWidget::class)
        ->assertSee('Total HADIR')
        ->assertSee('Total SAKIT')
        ->assertSee('Total IZIN')
        ->assertSee('Total ALPA')
        ->assertSee('1');
});

test('widget menampilkan persentase kehadiran yang benar', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user);
    Filament::setCurrentPanel(Filament::getPanel('student'));

    // 3 HADIR out of 4 total = 75%
    $kbms = Kbm::factory()->count(4)->create();

    Attendance::factory()->hadir()->create(['student_id' => $student->id, 'kbm_id' => $kbms[0]->id]);
    Attendance::factory()->hadir()->create(['student_id' => $student->id, 'kbm_id' => $kbms[1]->id]);
    Attendance::factory()->hadir()->create(['student_id' => $student->id, 'kbm_id' => $kbms[2]->id]);
    Attendance::factory()->alpa()->create(['student_id' => $student->id, 'kbm_id' => $kbms[3]->id]);

    Livewire::test(StudentAttendanceSummaryWidget::class)
        ->assertSee('Persentase Kehadiran')
        ->assertSee('75%');
});

test('siswa tanpa profil melihat pesan informatif', function () {
    $user = User::factory()->asSiswa()->create();

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('student'));

    Livewire::test(StudentAttendanceSummaryWidget::class)
        ->assertSee('Akun belum terhubung ke data siswa');
});
