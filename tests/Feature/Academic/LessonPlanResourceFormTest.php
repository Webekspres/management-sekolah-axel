<?php

use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->asAdmin()->create());
});

test('form resource rpp akademik menampilkan field approval lengkap', function () {
    $this->get('/admin/academic/lesson-plans/create')
        ->assertOk()
        ->assertSee('Guru Pengaju')
        ->assertSee('Mata Pelajaran')
        ->assertSee('Kelas')
        ->assertSee('Tanggal Pelaksanaan')
        ->assertSee('Dokumen RPP')
        ->assertSee('Status Approval');
});
