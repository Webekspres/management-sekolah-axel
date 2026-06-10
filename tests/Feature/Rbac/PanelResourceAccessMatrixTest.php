<?php

use App\Models\Teacher;
use App\Models\User;

test('super admin can access admin staff and lesson plans', function (): void {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get('/admin/data-personalia/staff')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/lesson-plans')
        ->assertOk();
});

test('guru cannot access admin staff or temporary access pages', function (): void {
    $guru = User::factory()->asGuru()->create();
    Teacher::factory()->create(['user_id' => $guru->id]);

    $this->actingAs($guru)
        ->get('/admin/data-personalia/staff')
        ->assertForbidden();

    $this->actingAs($guru)
        ->get('/admin/akses-sementara')
        ->assertForbidden();
});

test('siswa cannot access admin or guru lesson plans', function (): void {
    $siswa = User::factory()->asSiswa()->create();

    $this->actingAs($siswa)
        ->get('/admin/data-personalia/staff')
        ->assertForbidden();

    $this->actingAs($siswa)
        ->get('/guru/lesson-plans')
        ->assertForbidden();
});

test('kepala sekolah can access kepsek invoices and is blocked from guru panel', function (): void {
    $kepsek = User::factory()->asKepalaSekolah()->create();

    $this->actingAs($kepsek)
        ->get('/kepsek/invoices')
        ->assertOk();

    $this->actingAs($kepsek)
        ->get('/guru/lesson-plans')
        ->assertForbidden();
});

test('guru can access guru lesson plans and is blocked from student panel', function (): void {
    $guru = User::factory()->asGuru()->create();
    Teacher::factory()->create(['user_id' => $guru->id]);

    $this->actingAs($guru)
        ->get('/guru/lesson-plans')
        ->assertOk();

    $this->actingAs($guru)
        ->get('/student')
        ->assertForbidden();
});

test('siswa can access student panel and is blocked from kepsek', function (): void {
    $siswa = User::factory()->asSiswa()->create();

    $this->actingAs($siswa)
        ->get('/student')
        ->assertOk();

    $this->actingAs($siswa)
        ->get('/kepsek')
        ->assertForbidden();
});
