<?php

use App\Filament\Clusters\Academic\Resources\Schedules\ScheduleResource;
use App\Filament\Clusters\DataPersonalia\Resources\Students\StudentResource;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\TeacherResource;
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

test('admin dashboard shows archive navigation cards', function () {
    $user = User::factory()->asAdmin()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Sistem Informasi Sekolah')
        ->assertSee('Arsip')
        ->assertSee('Data Siswa')
        ->assertSee('Data Guru dan Staf')
        ->assertSee('Lihat Detail')
        ->assertSee(StudentResource::getUrl(panel: 'admin'), false)
        ->assertSee(TeacherResource::getUrl(panel: 'admin'), false)
        ->assertSee(ScheduleResource::getUrl(panel: 'admin'), false)
        ->assertSee('/admin', false);
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
