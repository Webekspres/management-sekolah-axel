<?php

// Feature: attendance-module

use App\Filament\Guru\Resources\Attendances\AttendanceResource as GuruAttendanceResource;
use App\Filament\Student\Resources\Attendances\AttendanceResource as StudentAttendanceResource;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Requirement 11.6: Unauthenticated redirect ───────────────────────────────

test('user tidak terautentikasi diredirect ke login saat akses admin attendance', function (): void {
    $this->get('/admin/attendances')
        ->assertRedirect();
});

test('user tidak terautentikasi diredirect ke login saat akses guru attendance', function (): void {
    $this->get('/guru/attendances')
        ->assertRedirect();
});

test('user tidak terautentikasi diredirect ke login saat akses student attendance', function (): void {
    $this->get('/student/attendances')
        ->assertRedirect();
});

// ─── Requirement 11.2: Guru hanya akses KBM miliknya ─────────────────────────

test('guru tidak dapat mengakses attendance KBM yang bukan miliknya', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('guru'));

    // Guru A — the authenticated user
    $userA = User::factory()->asGuru()->create();
    $teacherA = Teacher::factory()->create(['user_id' => $userA->id]);
    $classA = SchoolClass::factory()->create(['teacher_id' => $teacherA->id]);
    $scheduleA = Schedule::factory()->create([
        'class_id' => $classA->id,
        'teacher_id' => $teacherA->id,
    ]);
    $kbmA = Kbm::factory()->create(['schedule_id' => $scheduleA->id]);
    $studentA = Student::factory()->create(['class_id' => $classA->id]);
    Attendance::factory()->create(['kbm_id' => $kbmA->id, 'student_id' => $studentA->id]);

    // Guru B — a different teacher with their own KBMs and attendances
    $userB = User::factory()->asGuru()->create();
    $teacherB = Teacher::factory()->create(['user_id' => $userB->id]);
    $classB = SchoolClass::factory()->create(['teacher_id' => $teacherB->id]);
    $scheduleB = Schedule::factory()->create([
        'class_id' => $classB->id,
        'teacher_id' => $teacherB->id,
    ]);
    $kbmB = Kbm::factory()->create(['schedule_id' => $scheduleB->id]);
    $studentB = Student::factory()->create(['class_id' => $classB->id]);
    Attendance::factory()->create(['kbm_id' => $kbmB->id, 'student_id' => $studentB->id]);

    // Authenticate as Guru A
    $this->actingAs($userA);

    $records = GuruAttendanceResource::getEloquentQuery()->get();

    // None of the returned records should belong to Guru B's KBMs
    $kbmBIds = collect([$kbmB->id]);
    $hasGuruBRecords = $records->contains(
        fn (Attendance $record): bool => $kbmBIds->contains($record->kbm_id),
    );

    expect($hasGuruBRecords)->toBeFalse();
});

// ─── Requirement 11.5: Siswa hanya akses absensi dirinya ─────────────────────

test('siswa tidak dapat mengakses attendance siswa lain', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('student'));

    // Student A — the authenticated user
    $userA = User::factory()->asSiswa()->create();
    $classA = SchoolClass::factory()->create();
    $studentA = Student::factory()->create([
        'user_id' => $userA->id,
        'class_id' => $classA->id,
    ]);
    $teacherA = Teacher::factory()->create();
    $scheduleA = Schedule::factory()->create([
        'class_id' => $classA->id,
        'teacher_id' => $teacherA->id,
    ]);
    $kbmA = Kbm::factory()->create(['schedule_id' => $scheduleA->id]);
    Attendance::factory()->create(['kbm_id' => $kbmA->id, 'student_id' => $studentA->id]);

    // Student B — a different student with their own attendances
    $userB = User::factory()->asSiswa()->create();
    $classB = SchoolClass::factory()->create();
    $studentB = Student::factory()->create([
        'user_id' => $userB->id,
        'class_id' => $classB->id,
    ]);
    $teacherB = Teacher::factory()->create();
    $scheduleB = Schedule::factory()->create([
        'class_id' => $classB->id,
        'teacher_id' => $teacherB->id,
    ]);
    $kbmB = Kbm::factory()->create(['schedule_id' => $scheduleB->id]);
    Attendance::factory()->create(['kbm_id' => $kbmB->id, 'student_id' => $studentB->id]);

    // Authenticate as Student A
    $this->actingAs($userA);

    $records = StudentAttendanceResource::getEloquentQuery()->get();

    // All returned records must belong to Student A
    $allBelongToA = $records->every(
        fn (Attendance $record): bool => $record->student_id === $studentA->id,
    );
    expect($allBelongToA)->toBeTrue();

    // None of the returned records should belong to Student B
    $hasStudentBRecords = $records->contains(
        fn (Attendance $record): bool => $record->student_id === $studentB->id,
    );
    expect($hasStudentBRecords)->toBeFalse();
});
