<?php

// Feature: attendance-module

use App\Filament\Guru\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

/**
 * Helper: create a teacher with their own KBMs and attendance records.
 *
 * @return array{user: User, teacher: Teacher, kbms: Collection<int, Kbm>, attendances: Collection<int, Attendance>}
 */
function createTeacherWithAttendances(int $kbmCount = 2, int $studentsPerClass = 2): array
{
    $user = User::factory()->asGuru()->create();
    $teacher = Teacher::factory()->create(['user_id' => $user->id]);
    $schoolClass = SchoolClass::factory()->create(['teacher_id' => $teacher->id]);
    $students = Student::factory($studentsPerClass)->create(['class_id' => $schoolClass->id]);

    $kbms = collect();
    $attendances = collect();

    for ($i = 0; $i < $kbmCount; $i++) {
        $schedule = Schedule::factory()->create([
            'class_id' => $schoolClass->id,
            'teacher_id' => $teacher->id,
        ]);
        $kbm = Kbm::factory()->create(['schedule_id' => $schedule->id]);
        $kbms->push($kbm);

        foreach ($students as $student) {
            $attendance = Attendance::factory()->create([
                'kbm_id' => $kbm->id,
                'student_id' => $student->id,
            ]);
            $attendances->push($attendance);
        }
    }

    return compact('user', 'teacher', 'kbms', 'attendances');
}

/**
 * Property 5: Guru hanya bisa akses absensi KBM miliknya
 *
 * Validates: Requirements 1.5, 11.2
 */
it('guru hanya bisa akses absensi KBM miliknya sendiri', function (int $kbmCountA, int $kbmCountB, int $studentsPerClass): void {
    Filament::setCurrentPanel(Filament::getPanel('guru'));

    // Create teacher A with their KBMs and attendances
    ['user' => $userA, 'kbms' => $kbmsA] = createTeacherWithAttendances($kbmCountA, $studentsPerClass);

    // Create teacher B with their KBMs and attendances
    ['kbms' => $kbmsB] = createTeacherWithAttendances($kbmCountB, $studentsPerClass);

    // Authenticate as teacher A
    $this->actingAs($userA);

    // Call getEloquentQuery() as teacher A
    $records = AttendanceResource::getEloquentQuery()->get();

    // Collect all KBM IDs belonging to teacher B
    $kbmBIds = $kbmsB->pluck('id');

    // Validate: none of the returned records belong to teacher B's KBMs
    $hasTeacherBRecords = $records->contains(fn (Attendance $record): bool => $kbmBIds->contains($record->kbm_id));

    expect($hasTeacherBRecords)->toBeFalse();

    // Also validate: all returned records belong to teacher A's KBMs
    $kbmAIds = $kbmsA->pluck('id');
    $allBelongToA = $records->every(fn (Attendance $record): bool => $kbmAIds->contains($record->kbm_id));

    expect($allBelongToA)->toBeTrue();
})->with(function (): array {
    $cases = [];
    for ($i = 0; $i < 50; $i++) {
        $cases[] = [
            random_int(1, 3), // kbmCountA
            random_int(1, 3), // kbmCountB
            random_int(1, 3), // studentsPerClass
        ];
    }

    return $cases;
});

/**
 * Property 6: Siswa hanya melihat absensi dirinya sendiri
 *
 * Validates: Requirements 8.1, 11.5
 */
it('siswa hanya melihat absensi dirinya sendiri', function (int $attendanceCountA, int $attendanceCountB): void {
    Filament::setCurrentPanel(Filament::getPanel('student'));

    // Create student A with their own attendance records
    $userA = User::factory()->asSiswa()->create();
    $schoolClassA = SchoolClass::factory()->create();
    $studentA = Student::factory()->create([
        'user_id' => $userA->id,
        'class_id' => $schoolClassA->id,
    ]);
    $teacherA = Teacher::factory()->create();
    $scheduleA = Schedule::factory()->create([
        'class_id' => $schoolClassA->id,
        'teacher_id' => $teacherA->id,
    ]);
    for ($i = 0; $i < $attendanceCountA; $i++) {
        $kbm = Kbm::factory()->create(['schedule_id' => $scheduleA->id]);
        Attendance::factory()->create([
            'kbm_id' => $kbm->id,
            'student_id' => $studentA->id,
        ]);
    }

    // Create student B with their own attendance records
    $userB = User::factory()->asSiswa()->create();
    $schoolClassB = SchoolClass::factory()->create();
    $studentB = Student::factory()->create([
        'user_id' => $userB->id,
        'class_id' => $schoolClassB->id,
    ]);
    $teacherB = Teacher::factory()->create();
    $scheduleB = Schedule::factory()->create([
        'class_id' => $schoolClassB->id,
        'teacher_id' => $teacherB->id,
    ]);
    for ($i = 0; $i < $attendanceCountB; $i++) {
        $kbm = Kbm::factory()->create(['schedule_id' => $scheduleB->id]);
        Attendance::factory()->create([
            'kbm_id' => $kbm->id,
            'student_id' => $studentB->id,
        ]);
    }

    // Authenticate as student A
    $this->actingAs($userA);

    // Call getEloquentQuery() from the Student panel AttendanceResource
    $records = App\Filament\Student\Resources\Attendances\AttendanceResource::getEloquentQuery()->get();

    // Validate: all returned records have student_id === studentA->id
    $allBelongToA = $records->every(
        fn (Attendance $record): bool => $record->student_id === $studentA->id,
    );
    expect($allBelongToA)->toBeTrue();

    // Validate: none of the returned records have student_id === studentB->id
    $hasStudentBRecords = $records->contains(
        fn (Attendance $record): bool => $record->student_id === $studentB->id,
    );
    expect($hasStudentBRecords)->toBeFalse();
})->with(function (): array {
    $cases = [];
    for ($i = 0; $i < 50; $i++) {
        $cases[] = [
            random_int(1, 5), // attendanceCountA
            random_int(1, 5), // attendanceCountB
        ];
    }

    return $cases;
});
