<?php

use App\Filament\Kepsek\Resources\Attendances\AttendanceResource;
use App\Filament\Kepsek\Resources\Attendances\Pages\ListAttendances;
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

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->kepsek = User::factory()->asKepalaSekolah()->create();
    actingAs($this->kepsek);
    Filament::setCurrentPanel(Filament::getPanel('kepsek'));
});

test('kepsek dapat melihat semua attendance records', function () {
    $teacher = Teacher::factory()->create();
    $schoolClass = SchoolClass::factory()->create();
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);
    $kbm = Kbm::factory()->create(['schedule_id' => $schedule->id]);
    $student = Student::factory()->create(['class_id' => $schoolClass->id]);
    $attendance = Attendance::factory()->create([
        'kbm_id' => $kbm->id,
        'student_id' => $student->id,
        'status' => 'HADIR',
    ]);

    Livewire::test(ListAttendances::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$attendance]);
});

test('kepsek tidak dapat membuat attendance record', function () {
    expect(AttendanceResource::canCreate())->toBeFalse();
});

test('kepsek tidak dapat mengedit attendance record', function () {
    $teacher = Teacher::factory()->create();
    $schoolClass = SchoolClass::factory()->create();
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);
    $kbm = Kbm::factory()->create(['schedule_id' => $schedule->id]);
    $student = Student::factory()->create(['class_id' => $schoolClass->id]);
    $attendance = Attendance::factory()->create([
        'kbm_id' => $kbm->id,
        'student_id' => $student->id,
    ]);

    expect(AttendanceResource::canEdit($attendance))->toBeFalse();
});

test('kepsek tidak dapat menghapus attendance record', function () {
    $teacher = Teacher::factory()->create();
    $schoolClass = SchoolClass::factory()->create();
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);
    $kbm = Kbm::factory()->create(['schedule_id' => $schedule->id]);
    $student = Student::factory()->create(['class_id' => $schoolClass->id]);
    $attendance = Attendance::factory()->create([
        'kbm_id' => $kbm->id,
        'student_id' => $student->id,
    ]);

    expect(AttendanceResource::canDelete($attendance))->toBeFalse();
});

test('kepsek melihat nama guru di attendance list', function () {
    $teacherUser = User::factory()->asGuru()->create(['name' => 'Budi Santoso']);
    $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
    $schoolClass = SchoolClass::factory()->create();
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);
    $kbm = Kbm::factory()->create(['schedule_id' => $schedule->id]);
    $student = Student::factory()->create(['class_id' => $schoolClass->id]);
    $attendance = Attendance::factory()->create([
        'kbm_id' => $kbm->id,
        'student_id' => $student->id,
        'status' => 'HADIR',
    ]);

    Livewire::test(ListAttendances::class)
        ->assertSuccessful()
        ->assertTableColumnStateSet('teacher_name', 'Budi Santoso', record: $attendance);
});

test('kepsek dapat filter berdasarkan date range', function () {
    $teacher = Teacher::factory()->create();
    $schoolClass = SchoolClass::factory()->create();
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);

    $kbmInRange = Kbm::factory()->create([
        'schedule_id' => $schedule->id,
        'date' => '2024-03-15',
    ]);
    $kbmOutOfRange = Kbm::factory()->create([
        'schedule_id' => $schedule->id,
        'date' => '2024-01-10',
    ]);

    $student = Student::factory()->create(['class_id' => $schoolClass->id]);

    $attendanceInRange = Attendance::factory()->create([
        'kbm_id' => $kbmInRange->id,
        'student_id' => $student->id,
        'status' => 'HADIR',
    ]);
    $attendanceOutOfRange = Attendance::factory()->create([
        'kbm_id' => $kbmOutOfRange->id,
        'student_id' => $student->id,
        'status' => 'ALPA',
    ]);

    Livewire::test(ListAttendances::class)
        ->filterTable('date_range', [
            'date_from' => '2024-03-01',
            'date_until' => '2024-03-31',
        ])
        ->assertCanSeeTableRecords([$attendanceInRange])
        ->assertCanNotSeeTableRecords([$attendanceOutOfRange]);
});
