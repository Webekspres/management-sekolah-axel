<?php

use App\Models\User;

test('form resource kbm akademik menampilkan field approval lengkap', function () {
    $this->actingAs(User::factory()->asAdmin()->create())
        ->get('/admin/academic/kbms/create')
        ->assertOk()
        ->assertSee('Jadwal')
        ->assertSee('RPP Approved')
        ->assertSee('Tanggal KBM')
        ->assertSee('Status Approval')
        ->assertSee('Catatan Proses Belajar');
});
