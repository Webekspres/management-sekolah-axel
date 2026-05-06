<?php

use App\Models\ActivityLog;
use App\Models\LessonPlan;
use App\Models\User;

test('guru dapat mengajukan rpp draft untuk approval', function () {
    $guru = User::factory()->asGuru()->create();
    $lessonPlan = LessonPlan::factory()->create([
        'status' => 'DRAFT',
        'revision_note' => null,
    ]);

    $lessonPlan->submitForApproval($guru);

    expect($lessonPlan->refresh()->status)->toBe('PENDING')
        ->and($lessonPlan->revision_note)->toBeNull();

    $this->assertDatabaseHas(ActivityLog::class, [
        'user_id' => $guru->id,
        'action' => 'lesson_plan_submitted',
        'entity_type' => LessonPlan::class,
        'entity_id' => $lessonPlan->id,
    ]);
});

test('kepsek dapat meminta revisi untuk rpp pending', function () {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $lessonPlan = LessonPlan::factory()->create([
        'status' => 'PENDING',
        'revision_note' => null,
    ]);

    $lessonPlan->markAsRevised($kepsek, 'Lengkapi indikator pembelajaran dan asesmen.');

    expect($lessonPlan->refresh()->status)->toBe('REVISED')
        ->and($lessonPlan->revision_note)->toBe('Lengkapi indikator pembelajaran dan asesmen.');

    $this->assertDatabaseHas(ActivityLog::class, [
        'user_id' => $kepsek->id,
        'action' => 'lesson_plan_revised',
        'entity_type' => LessonPlan::class,
        'entity_id' => $lessonPlan->id,
    ]);
});

test('kepsek dapat menyetujui rpp pending', function () {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $lessonPlan = LessonPlan::factory()->create([
        'status' => 'PENDING',
        'revision_note' => 'Catatan sebelumnya',
    ]);

    $lessonPlan->approve($kepsek);

    expect($lessonPlan->refresh()->status)->toBe('APPROVED')
        ->and($lessonPlan->revision_note)->toBeNull();

    $this->assertDatabaseHas(ActivityLog::class, [
        'user_id' => $kepsek->id,
        'action' => 'lesson_plan_approved',
        'entity_type' => LessonPlan::class,
        'entity_id' => $lessonPlan->id,
    ]);
});

test('kepsek dapat menyetujui rpp yang sudah direvisi', function () {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $lessonPlan = LessonPlan::factory()->create([
        'status' => 'REVISED',
        'revision_note' => 'Catatan revisi sebelumnya',
    ]);

    $lessonPlan->approve($kepsek);

    expect($lessonPlan->refresh()->status)->toBe('APPROVED')
        ->and($lessonPlan->revision_note)->toBeNull();
});

test('rpp tidak bisa disetujui jika status masih draft', function () {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $lessonPlan = LessonPlan::factory()->create([
        'status' => 'DRAFT',
    ]);

    expect(fn () => $lessonPlan->approve($kepsek))
        ->toThrow(DomainException::class);
});
