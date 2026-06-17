<?php

use App\Models\ActivityLog;
use App\Models\Kbm;
use App\Models\User;

test('guru dapat mengajukan laporan kbm draft untuk approval', function () {
    $guru = User::factory()->asGuru()->create();
    $kbm = Kbm::factory()->create([
        'status' => 'DRAFT',
        'revision_note' => null,
    ]);

    $kbm->submitForApproval($guru);

    expect($kbm->refresh()->status)->toBe('PENDING')
        ->and($kbm->revision_note)->toBeNull();

    $this->assertDatabaseHas(ActivityLog::class, [
        'user_id' => $guru->id,
        'action' => 'kbm_submitted',
        'entity_type' => Kbm::class,
        'entity_id' => $kbm->id,
    ]);
});

test('kepsek dapat meminta revisi laporan kbm pending', function () {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $kbm = Kbm::factory()->pending()->create([
        'revision_note' => null,
    ]);

    $kbm->markAsRevised($kepsek, 'Lengkapi dokumentasi dan catatan solusi.');

    expect($kbm->refresh()->status)->toBe('REVISED')
        ->and($kbm->revision_note)->toBe('Lengkapi dokumentasi dan catatan solusi.');

    $this->assertDatabaseHas(ActivityLog::class, [
        'user_id' => $kepsek->id,
        'action' => 'kbm_revised',
        'entity_type' => Kbm::class,
        'entity_id' => $kbm->id,
    ]);
});

test('kepsek dapat menyetujui laporan kbm pending', function () {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $kbm = Kbm::factory()->pending()->create([
        'revision_note' => 'Catatan lama',
    ]);

    $kbm->approve($kepsek);

    expect($kbm->refresh()->status)->toBe('APPROVED')
        ->and($kbm->revision_note)->toBeNull();

    $this->assertDatabaseHas(ActivityLog::class, [
        'user_id' => $kepsek->id,
        'action' => 'kbm_approved',
        'entity_type' => Kbm::class,
        'entity_id' => $kbm->id,
    ]);
});

test('laporan kbm tidak bisa diajukan dari status approved', function () {
    $guru = User::factory()->asGuru()->create();
    $kbm = Kbm::factory()->approved()->create();

    expect(fn () => $kbm->submitForApproval($guru))
        ->toThrow(DomainException::class);
});
