<?php

use App\Filament\Widgets\AttendanceSummaryWidget;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Helper: create a guru user with teacher profile, class, schedule, and KBM for today.
 *
 * @return array{user: User, teacher: Teacher, schoolClass: SchoolClass, schedule: Schedule, kbm: Kbm}
 */
function createGuruWithTodayKbm(): array
{
    $user = User::factory()->asGuru()->create();
    $teacher = Teacher::factory()->create(['user_id' => $user->id]);
    $schoolClass = SchoolClass::factory()->create(['teacher_id' => $teacher->id]);
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);
    $kbm = Kbm::factory()->create([
        'schedule_id' => $schedule->id,
        'date' => today()->toDateString(),
    ]);

    return compact('user', 'teacher', 'schoolClass', 'schedule', 'kbm');
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('widget menampilkan jumlah absensi hari ini yang benar untuk guru', function () {
    ['user' => $user, 'schoolClass' => $schoolClass, 'kbm' => $kbm] = createGuruWithTodayKbm();

    $students = Student::factory(3)->create(['class_id' => $schoolClass->id]);

    // Create 2 attendance records for today's KBM
    Attendance::factory()->create(['kbm_id' => $kbm->id, 'student_id' => $students[0]->id, 'status' => 'HADIR']);
    Attendance::factory()->create(['kbm_id' => $kbm->id, 'student_id' => $students[1]->id, 'status' => 'SAKIT']);

    // Create attendance for a different KBM (yesterday) — should NOT be counted
    $kbmYesterday = Kbm::factory()->create([
        'schedule_id' => $kbm->schedule_id,
        'date' => today()->subDay()->toDateString(),
    ]);
    Attendance::factory()->create(['kbm_id' => $kbmYesterday->id, 'student_id' => $students[2]->id, 'status' => 'HADIR']);

    $this->actingAs($user);

    Livewire::test(AttendanceSummaryWidget::class)
        ->assertSee('Total Absensi Hari Ini')
        ->assertSee('2');
});

test('widget menampilkan jumlah HADIR yang benar untuk guru', function () {
    ['user' => $user, 'schoolClass' => $schoolClass, 'kbm' => $kbm] = createGuruWithTodayKbm();

    $students = Student::factory(4)->create(['class_id' => $schoolClass->id]);

    // 2 HADIR, 1 SAKIT, 1 ALPA for today
    Attendance::factory()->hadir()->create(['kbm_id' => $kbm->id, 'student_id' => $students[0]->id]);
    Attendance::factory()->hadir()->create(['kbm_id' => $kbm->id, 'student_id' => $students[1]->id]);
    Attendance::factory()->sakit()->create(['kbm_id' => $kbm->id, 'student_id' => $students[2]->id]);
    Attendance::factory()->alpa()->create(['kbm_id' => $kbm->id, 'student_id' => $students[3]->id]);

    $this->actingAs($user);

    Livewire::test(AttendanceSummaryWidget::class)
        ->assertSee('Total HADIR Hari Ini')
        ->assertSee('2');
});

test('widget menampilkan siswa dengan kehadiran di bawah 75% yang benar', function () {
    ['user' => $user, 'schoolClass' => $schoolClass, 'kbm' => $kbm] = createGuruWithTodayKbm();

    $students = Student::factory(3)->create(['class_id' => $schoolClass->id]);

    // Student 0: 1 HADIR out of 4 = 25% — below threshold
    Attendance::factory()->hadir()->create(['kbm_id' => $kbm->id, 'student_id' => $students[0]->id]);
    $extraKbms = Kbm::factory(3)->create(['schedule_id' => $kbm->schedule_id]);
    foreach ($extraKbms as $extraKbm) {
        Attendance::factory()->alpa()->create(['kbm_id' => $extraKbm->id, 'student_id' => $students[0]->id]);
    }

    // Student 1: 3 HADIR out of 4 = 75% — exactly at threshold, NOT below
    Attendance::factory()->hadir()->create(['kbm_id' => $kbm->id, 'student_id' => $students[1]->id]);
    foreach ($extraKbms as $extraKbm) {
        Attendance::factory()->hadir()->create(['kbm_id' => $extraKbm->id, 'student_id' => $students[1]->id]);
    }

    // Student 2: no attendance records — should not be counted
    // (whereHas('attendances') filters them out)

    $this->actingAs($user);

    Livewire::test(AttendanceSummaryWidget::class)
        ->assertSee('Siswa Kehadiran < 75%')
        ->assertSee('1');
});

test('widget tidak terlihat untuk role super_admin', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    expect(AttendanceSummaryWidget::canView())->toBeFalse();
});

test('widget terlihat untuk role kepala_sekolah', function () {
    $kepsek = User::factory()->asKepalaSekolah()->create();

    $this->actingAs($kepsek);

    expect(AttendanceSummaryWidget::canView())->toBeTrue();
});

test('widget kepsek menampilkan data semua kelas', function () {
    $kepsek = User::factory()->asKepalaSekolah()->create();

    // Create two different teachers/classes/KBMs for today
    ['kbm' => $kbm1, 'schoolClass' => $class1] = createGuruWithTodayKbm();
    ['kbm' => $kbm2, 'schoolClass' => $class2] = createGuruWithTodayKbm();

    $student1 = Student::factory()->create(['class_id' => $class1->id]);
    $student2 = Student::factory()->create(['class_id' => $class2->id]);

    Attendance::factory()->hadir()->create(['kbm_id' => $kbm1->id, 'student_id' => $student1->id]);
    Attendance::factory()->hadir()->create(['kbm_id' => $kbm2->id, 'student_id' => $student2->id]);

    $this->actingAs($kepsek);

    Livewire::test(AttendanceSummaryWidget::class)
        ->assertSee('Total Absensi Hari Ini')
        ->assertSee('2');
});
