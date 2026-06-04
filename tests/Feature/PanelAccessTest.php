<?php

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
        ->assertSee('Total Siswa')
        ->assertSee('Total Guru dan Staf')
        ->assertSee('Kelas Aktif')
        ->assertSee('Kehadiran Hari Ini');
});

test('guru dashboard shows phase 2 analytics widgets', function () {
    $teacher = Teacher::factory()->create();
    $user = $teacher->user;

    $this->actingAs($user)
        ->get('/guru')
        ->assertOk()
        ->assertSee('Jadwal Hari Ini')
        ->assertSee('KBM Terbaru')
        ->assertSee('Ringkasan Absensi Kelas');
});

test('kepsek dashboard shows phase 2 analytics widgets', function () {
    $user = User::factory()->asKepalaSekolah()->create();

    $this->actingAs($user)
        ->get('/kepsek')
        ->assertOk()
        ->assertSee('Kehadiran hari ini')
        ->assertSee('KBM hari ini')
        ->assertSee('Pengumuman terbaru');
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
