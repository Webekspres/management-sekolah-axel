<?php

use App\Filament\Clusters\Academic\Resources\Schedules\ScheduleResource;
use App\Filament\Clusters\DataPersonalia\Resources\Students\StudentResource;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\TeacherResource;
use App\Models\Teacher;
use App\Models\User;

dataset('panel routes', [
    'admin' => ['/admin'],
    'kepsek' => ['/kepsek'],
    'guru' => ['/guru'],
    'student' => ['/student'],
]);

test('inactive users cannot access panels', function (string $panelPath) {
    $user = User::factory()->inactive()->asAdmin()->create();

    $this->actingAs($user)
        ->get($panelPath)
        ->assertForbidden();
})->with('panel routes');

test('admin dashboard shows phase 2 analytics cards', function () {
    $user = User::factory()->asAdmin()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Dashboard Admin')
        ->assertSee('Ringkasan operasional sekolah hari ini')
        ->assertSee('Total Siswa')
        ->assertSee('Total Guru dan Staf')
        ->assertSee('Kelas Aktif')
        ->assertSee('Kehadiran Hari Ini')
        ->assertSee(StudentResource::getUrl(panel: 'admin'), false)
        ->assertSee(TeacherResource::getUrl(panel: 'admin'), false)
        ->assertSee(ScheduleResource::getUrl(panel: 'admin'), false)
        ->assertSee('/admin', false);
});

test('guru dashboard shows phase 2 analytics widgets', function () {
    $teacher = Teacher::factory()->create();
    $user = $teacher->user;

    $this->actingAs($user)
        ->get('/guru')
        ->assertOk()
        ->assertSee('Dashboard Guru')
        ->assertSee('Jadwal Hari Ini')
        ->assertSee('KBM Terbaru')
        ->assertSee('Ringkasan Absensi Kelas');
});

test('kepsek dashboard shows phase 2 analytics widgets', function () {
    $user = User::factory()->asKepalaSekolah()->create();

    $this->actingAs($user)
        ->get('/kepsek')
        ->assertOk()
        ->assertSee('Dashboard Kepala Sekolah')
        ->assertSee('Overview Kehadiran')
        ->assertSee('Overview KBM')
        ->assertSee('Pengumuman Terbaru');
});

test('admin dapat mengakses panel kepsek', function () {
    $user = User::factory()->asAdmin()->create();

    $this->actingAs($user)
        ->get('/kepsek')
        ->assertOk();
});

test('admin dapat mengakses menu approval rpp dan kbm di panel admin', function () {
    $user = User::factory()->asAdmin()->create();

    $this->actingAs($user)
        ->get('/admin/lesson-plans')
        ->assertOk();

    $this->actingAs($user)
        ->get('/admin/kbms')
        ->assertOk();
});
