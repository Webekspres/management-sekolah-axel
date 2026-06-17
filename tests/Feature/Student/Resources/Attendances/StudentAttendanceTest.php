<?php

use App\Filament\Student\Resources\Attendances\AttendanceResource;
use App\Filament\Student\Resources\Attendances\Pages\ListAttendances;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Helper: create a siswa user with a linked student profile, class, schedule, KBM, and attendance records.
 *
 * @return array{user: User, student: Student, schoolClass: SchoolClass, attendances: Collection}
 */
function createStudentWithAttendances(int $count = 3): array
{
    $user = User::factory()->asSiswa()->create();
    $schoolClass = SchoolClass::factory()->create();
    $student = Student::factory()->create([
        'user_id' => $user->id,
        'class_id' => $schoolClass->id,
    ]);

    $teacher = Teacher::factory()->create();
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);

    $attendances = collect();
    for ($i = 0; $i < $count; $i++) {
        $kbm = Kbm::factory()->create(['schedule_id' => $schedule->id]);
        $attendance = Attendance::factory()->create([
            'kbm_id' => $kbm->id,
            'student_id' => $student->id,
            'status' => 'HADIR',
        ]);
        $attendances->push($attendance);
    }

    return compact('user', 'student', 'schoolClass', 'attendances');
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('student'));
});

test('siswa dapat melihat attendance records miliknya sendiri', function () {
    ['user' => $user, 'attendances' => $attendances] = createStudentWithAttendances(3);

    actingAs($user);

    Livewire::test(ListAttendances::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($attendances);
});

test('siswa tidak dapat melihat attendance records siswa lain', function () {
    // Student A with their own attendances
    ['user' => $userA] = createStudentWithAttendances(2);

    // Student B with their own attendances
    ['attendances' => $attendancesB] = createStudentWithAttendances(2);

    actingAs($userA);

    Livewire::test(ListAttendances::class)
        ->assertSuccessful()
        ->assertCanNotSeeTableRecords($attendancesB);
});

test('siswa tanpa profil student tidak dapat mengakses halaman absensi', function () {
    $user = User::factory()->asSiswa()->create();

    actingAs($user);

    expect(AttendanceResource::canAccess())->toBeFalse();

    Livewire::test(ListAttendances::class)
        ->assertForbidden();
});

test('siswa dapat filter berdasarkan status', function () {
    $user = User::factory()->asSiswa()->create();
    $schoolClass = SchoolClass::factory()->create();
    $student = Student::factory()->create([
        'user_id' => $user->id,
        'class_id' => $schoolClass->id,
    ]);
    $teacher = Teacher::factory()->create();
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);

    $kbm1 = Kbm::factory()->create(['schedule_id' => $schedule->id]);
    $kbm2 = Kbm::factory()->create(['schedule_id' => $schedule->id]);

    $attendanceHadir = Attendance::factory()->create([
        'kbm_id' => $kbm1->id,
        'student_id' => $student->id,
        'status' => 'HADIR',
    ]);
    $attendanceAlpa = Attendance::factory()->create([
        'kbm_id' => $kbm2->id,
        'student_id' => $student->id,
        'status' => 'ALPA',
    ]);

    actingAs($user);

    Livewire::test(ListAttendances::class)
        ->filterTable('status', 'HADIR')
        ->assertCanSeeTableRecords([$attendanceHadir])
        ->assertCanNotSeeTableRecords([$attendanceAlpa]);
});

test('siswa tidak dapat membuat attendance record', function () {
    expect(AttendanceResource::canCreate())->toBeFalse();
});

test('siswa tidak dapat mengedit attendance record', function () {
    ['user' => $user, 'attendances' => $attendances] = createStudentWithAttendances(1);

    actingAs($user);

    expect(AttendanceResource::canEdit($attendances->first()))->toBeFalse();
});

test('siswa tidak dapat menghapus attendance record', function () {
    ['user' => $user, 'attendances' => $attendances] = createStudentWithAttendances(1);

    actingAs($user);

    expect(AttendanceResource::canDelete($attendances->first()))->toBeFalse();
});
