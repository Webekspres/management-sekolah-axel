<?php

use App\Models\Teacher;
use App\Models\User;

test('guru dapat membuka form create kbm', function () {
    $guru = User::factory()->asGuru()->create();
    Teacher::factory()->create(['user_id' => $guru->id]);

    $this->actingAs($guru)
        ->get('/guru/academic/kbms/create')
        ->assertOk()
        ->assertSee('Jadwal')
        ->assertSee('RPP Approved')
        ->assertSee('Tanggal KBM');
});
