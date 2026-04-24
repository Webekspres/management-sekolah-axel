<?php

use App\Models\Kbm;
use App\Models\LessonPlan;
use App\Models\Schedule;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\KbmPolicy;
use App\Policies\LessonPlanPolicy;

test('lesson plan policy follows role and status matrix', function () {
    $policy = app(LessonPlanPolicy::class);

    $admin = User::factory()->asAdmin()->create();
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $siswa = User::factory()->asSiswa()->create();

    $guruUser = User::factory()->asGuru()->create();
    $guruTeacher = Teacher::factory()->create(['user_id' => $guruUser->id]);

    $otherGuruUser = User::factory()->asGuru()->create();
    $otherGuruTeacher = Teacher::factory()->create(['user_id' => $otherGuruUser->id]);

    $guruDraftLessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $guruTeacher->id,
        'status' => 'DRAFT',
    ]);

    $guruPendingLessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $guruTeacher->id,
        'status' => 'PENDING',
    ]);

    $guruApprovedLessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $guruTeacher->id,
        'status' => 'APPROVED',
    ]);

    $otherGuruLessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $otherGuruTeacher->id,
        'status' => 'DRAFT',
    ]);

    expect($policy->create($admin))->toBeTrue()
        ->and($policy->update($admin, $guruApprovedLessonPlan))->toBeTrue()
        ->and($policy->delete($admin, $guruApprovedLessonPlan))->toBeTrue();

    expect($policy->create($kepsek))->toBeFalse()
        ->and($policy->viewAny($kepsek))->toBeTrue()
        ->and($policy->update($kepsek, $guruDraftLessonPlan))->toBeTrue()
        ->and($policy->delete($kepsek, $guruDraftLessonPlan))->toBeFalse();

    expect($policy->create($guruUser))->toBeTrue()
        ->and($policy->view($guruUser, $guruDraftLessonPlan))->toBeTrue()
        ->and($policy->view($guruUser, $otherGuruLessonPlan))->toBeFalse()
        ->and($policy->update($guruUser, $guruDraftLessonPlan))->toBeTrue()
        ->and($policy->update($guruUser, $guruPendingLessonPlan))->toBeTrue()
        ->and($policy->update($guruUser, $guruApprovedLessonPlan))->toBeFalse()
        ->and($policy->delete($guruUser, $guruDraftLessonPlan))->toBeTrue()
        ->and($policy->delete($guruUser, $guruApprovedLessonPlan))->toBeFalse();

    expect($policy->viewAny($siswa))->toBeFalse()
        ->and($policy->view($siswa, $guruDraftLessonPlan))->toBeFalse()
        ->and($policy->create($siswa))->toBeFalse();
});

test('kbm policy follows role and status matrix', function () {
    $policy = app(KbmPolicy::class);

    $admin = User::factory()->asAdmin()->create();
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $siswa = User::factory()->asSiswa()->create();

    $guruUser = User::factory()->asGuru()->create();
    $guruTeacher = Teacher::factory()->create(['user_id' => $guruUser->id]);

    $otherGuruUser = User::factory()->asGuru()->create();
    $otherGuruTeacher = Teacher::factory()->create(['user_id' => $otherGuruUser->id]);

    $guruSchedule = Schedule::factory()->create(['teacher_id' => $guruTeacher->id]);
    $otherGuruSchedule = Schedule::factory()->create(['teacher_id' => $otherGuruTeacher->id]);

    $guruDraftKbm = Kbm::factory()->create([
        'schedule_id' => $guruSchedule->id,
        'status' => 'DRAFT',
    ]);

    $guruPendingKbm = Kbm::factory()->create([
        'schedule_id' => $guruSchedule->id,
        'status' => 'PENDING',
    ]);

    $guruApprovedKbm = Kbm::factory()->create([
        'schedule_id' => $guruSchedule->id,
        'status' => 'APPROVED',
    ]);

    $otherGuruKbm = Kbm::factory()->create([
        'schedule_id' => $otherGuruSchedule->id,
        'status' => 'DRAFT',
    ]);

    expect($policy->create($admin))->toBeTrue()
        ->and($policy->update($admin, $guruApprovedKbm))->toBeTrue()
        ->and($policy->delete($admin, $guruApprovedKbm))->toBeTrue();

    expect($policy->create($kepsek))->toBeFalse()
        ->and($policy->viewAny($kepsek))->toBeTrue()
        ->and($policy->update($kepsek, $guruDraftKbm))->toBeTrue()
        ->and($policy->delete($kepsek, $guruDraftKbm))->toBeFalse();

    expect($policy->create($guruUser))->toBeTrue()
        ->and($policy->view($guruUser, $guruDraftKbm))->toBeTrue()
        ->and($policy->view($guruUser, $otherGuruKbm))->toBeFalse()
        ->and($policy->update($guruUser, $guruDraftKbm))->toBeTrue()
        ->and($policy->update($guruUser, $guruPendingKbm))->toBeTrue()
        ->and($policy->update($guruUser, $guruApprovedKbm))->toBeFalse()
        ->and($policy->delete($guruUser, $guruDraftKbm))->toBeTrue()
        ->and($policy->delete($guruUser, $guruApprovedKbm))->toBeFalse();

    expect($policy->viewAny($siswa))->toBeFalse()
        ->and($policy->view($siswa, $guruDraftKbm))->toBeFalse()
        ->and($policy->create($siswa))->toBeFalse();
});
