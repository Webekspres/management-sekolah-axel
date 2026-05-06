<?php

use App\Filament\Guru\Resources\Attendances\AttendanceResource;
use App\Filament\Guru\Resources\Kbms\Pages\InputKbmAttendance;
use App\Filament\Guru\Resources\Kbms\Pages\ListKbms;
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

/**
 * Helper: create a guru user with a teacher profile, a class, schedule, and KBM.
 *
 * @return array{user: User, teacher: Teacher, schoolClass: SchoolClass, schedule: Schedule, kbm: Kbm}
 */
function createGuruWithKbm(): array
{
    $user = User::factory()->asGuru()->create();
    $teacher = Teacher::factory()->create(['user_id' => $user->id]);
    $schoolClass = SchoolClass::factory()->create(['teacher_id' => $teacher->id]);
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);
    $kbm = Kbm::factory()->create(['schedule_id' => $schedule->id]);

    return compact('user', 'teacher', 'schoolClass', 'schedule', 'kbm');
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('guru'));
});

test('guru dapat melihat action "Input Absensi" di KBM table', function () {
    ['user' => $user, 'kbm' => $kbm] = createGuruWithKbm();

    actingAs($user);

    Livewire::test(ListKbms::class)
        ->assertSuccessful()
        ->assertTableActionExists('input_absensi', record: $kbm);
});

test('guru dapat akses halaman input absensi untuk KBM miliknya', function () {
    ['user' => $user, 'schoolClass' => $schoolClass, 'kbm' => $kbm] = createGuruWithKbm();

    Student::factory(3)->create(['class_id' => $schoolClass->id]);

    actingAs($user);

    Livewire::test(InputKbmAttendance::class, ['record' => $kbm->getRouteKey()])
        ->assertSuccessful();
});

test('guru tidak dapat mengakses attendance KBM guru lain', function () {
    // Guru A
    ['user' => $userA, 'kbm' => $kbmA] = createGuruWithKbm();

    // Guru B with their own attendance records
    ['kbm' => $kbmB] = createGuruWithKbm();
    $studentB = Student::factory()->create(['class_id' => $kbmB->schedule->class_id]);
    Attendance::factory()->create(['kbm_id' => $kbmB->id, 'student_id' => $studentB->id]);

    actingAs($userA);

    // getEloquentQuery for guru A should not return records from guru B's KBMs
    $query = AttendanceResource::getEloquentQuery();
    $records = $query->get();

    $kbmBIds = collect([$kbmB->id]);
    $hasOtherTeacherRecords = $records->contains(fn ($record) => $kbmBIds->contains($record->kbm_id));

    expect($hasOtherTeacherRecords)->toBeFalse();
});

test('bulk set status semua siswa menyimpan absensi untuk seluruh siswa', function () {
    ['user' => $user, 'schoolClass' => $schoolClass, 'kbm' => $kbm] = createGuruWithKbm();

    $students = Student::factory(2)->create(['class_id' => $schoolClass->id]);

    actingAs($user);

    Livewire::test(InputKbmAttendance::class, ['record' => $kbm->getRouteKey()])
        ->call('setAllStatus', 'SAKIT')
        ->assertNotified('Absensi berhasil diperbarui');

    expect(Attendance::where('kbm_id', $kbm->id)->count())->toBe(2)
        ->and(
            Attendance::where('kbm_id', $kbm->id)
                ->where('status', 'SAKIT')
                ->count()
        )->toBe(2);
});

test('guru dapat mengubah status siswa sebagai pengecualian dari status mayoritas', function () {
    ['user' => $user, 'schoolClass' => $schoolClass, 'kbm' => $kbm] = createGuruWithKbm();
    $students = Student::factory(3)->create(['class_id' => $schoolClass->id]);

    actingAs($user);

    Livewire::test(InputKbmAttendance::class, ['record' => $kbm->getRouteKey()])
        ->call('setAllStatus', 'HADIR')
        ->call('setStudentStatus', $students->first()->id, 'IZIN');

    expect(
        Attendance::where('kbm_id', $kbm->id)
            ->where('status', 'HADIR')
            ->count()
    )->toBe(2)
        ->and(
            Attendance::where('kbm_id', $kbm->id)
                ->where('status', 'IZIN')
                ->count()
        )->toBe(1);
});

test('kolom status absensi menampilkan progress yang benar', function () {
    ['user' => $user, 'schoolClass' => $schoolClass, 'kbm' => $kbm] = createGuruWithKbm();

    // Create 3 students, attend 2
    $students = Student::factory(3)->create(['class_id' => $schoolClass->id]);
    Attendance::factory()->create(['kbm_id' => $kbm->id, 'student_id' => $students[0]->id, 'status' => 'HADIR']);
    Attendance::factory()->create(['kbm_id' => $kbm->id, 'student_id' => $students[1]->id, 'status' => 'SAKIT']);

    actingAs($user);

    Livewire::test(ListKbms::class)
        ->assertSuccessful()
        ->assertTableColumnStateSet('attendance_status', '2/3 diabsen', record: $kbm);
});

test('kelas kosong menampilkan empty state informatif di halaman absensi', function () {
    ['user' => $user, 'kbm' => $kbm] = createGuruWithKbm();
    // No students in the class

    actingAs($user);

    Livewire::test(InputKbmAttendance::class, ['record' => $kbm->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('Tidak ada siswa di kelas ini');
});
