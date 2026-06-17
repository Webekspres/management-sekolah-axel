<?php

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;

test('admin dashboard loads analytics widgets without javascript errors', function (): void {
    $admin = User::factory()->asAdmin()->create();

    Student::factory()->count(2)->create();
    Teacher::factory()->count(3)->create();

    $level = Level::factory()->create();
    $homeroomTeacher = Teacher::factory()->create();
    $activeYear = AcademicYear::factory()->active()->create();

    SchoolClass::factory()->create([
        'level_id' => $level->id,
        'teacher_id' => $homeroomTeacher->id,
        'academic_year_id' => $activeYear->id,
    ]);

    $kbmToday = Kbm::factory()->create(['date' => today()->toDateString()]);
    Attendance::factory()->hadir()->create(['kbm_id' => $kbmToday->id]);

    visitAuthenticated($admin, '/admin')
        ->assertSee('Total Siswa')
        ->assertSee('Total Guru dan Staf')
        ->assertSee('Kelas Aktif')
        ->assertSee('Kehadiran Hari Ini')
        ->assertNoSmoke();
});

test('guru dashboard loads schedule and attendance widgets without javascript errors', function (): void {
    $teacher = Teacher::factory()->create();
    $user = $teacher->user;

    visitAuthenticated($user, '/guru')
        ->assertSee('Jadwal Hari Ini')
        ->assertSee('KBM Terbaru')
        ->assertSee('Ringkasan Absensi Kelas')
        ->assertNoSmoke();
});

test('kepsek dashboard loads overview widgets without javascript errors', function (): void {
    $user = User::factory()->asKepalaSekolah()->create();

    visitAuthenticated($user, '/kepsek')
        ->assertSee('Kehadiran hari ini')
        ->assertSee('KBM hari ini')
        ->assertSee('Pengumuman terbaru')
        ->assertNoSmoke();
});

test('student dashboard loads without javascript errors', function (): void {
    $user = User::factory()->asSiswa()->create();
    Student::factory()->create(['user_id' => $user->id]);

    visitAuthenticated($user, '/student')
        ->assertNoSmoke();
});

test('inactive users are blocked from panels in the browser', function (string $panelPath): void {
    $user = User::factory()->inactive()->asAdmin()->create();

    visitAuthenticated($user, $panelPath)
        ->assertSee('403');
})->with([
    'admin' => ['/admin'],
    'kepsek' => ['/kepsek'],
    'guru' => ['/guru'],
    'student' => ['/student'],
]);

test('authenticated users are redirected from home to their panel', function (callable $createUser, string $expectedPath): void {
    $user = $createUser();

    visitAuthenticated($user, '/')
        ->assertPathIs($expectedPath)
        ->assertNoSmoke();
})->with([
    'admin' => [fn (): User => User::factory()->asAdmin()->create(), '/admin'],
    'kepsek' => [fn (): User => User::factory()->asKepalaSekolah()->create(), '/kepsek'],
    'guru' => [fn (): User => User::factory()->asGuru()->create(), '/guru'],
    'student' => [fn (): User => User::factory()->asSiswa()->create(), '/student'],
]);
