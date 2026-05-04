<?php

use App\Models\Grade;
use App\Models\Schedule;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\GradePolicy;

test('grade policy follows role and schedule ownership matrix', function () {
    $policy = app(GradePolicy::class);

    $admin = User::factory()->asAdmin()->create();
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $siswa = User::factory()->asSiswa()->create();

    $guruUser = User::factory()->asGuru()->create();
    $guruTeacher = Teacher::factory()->create(['user_id' => $guruUser->id]);

    $otherGuruUser = User::factory()->asGuru()->create();
    $otherGuruTeacher = Teacher::factory()->create(['user_id' => $otherGuruUser->id]);

    // Create schedules for each teacher
    $guruSchedule = Schedule::factory()->create(['teacher_id' => $guruTeacher->id]);
    $otherGuruSchedule = Schedule::factory()->create(['teacher_id' => $otherGuruTeacher->id]);

    // Grade linked to guru's schedule via subject_id
    $guruGrade = Grade::factory()->create([
        'subject_id' => $guruSchedule->subject_id,
    ]);

    // Grade linked to other guru's schedule via subject_id
    $otherGuruGrade = Grade::factory()->create([
        'subject_id' => $otherGuruSchedule->subject_id,
    ]);

    // --- super_admin: full access ---
    expect($policy->viewAny($admin))->toBeTrue()
        ->and($policy->view($admin, $guruGrade))->toBeTrue()
        ->and($policy->create($admin))->toBeTrue()
        ->and($policy->update($admin, $guruGrade))->toBeTrue()
        ->and($policy->delete($admin, $guruGrade))->toBeTrue();

    // --- kepala_sekolah: can view, cannot create/update/delete ---
    expect($policy->viewAny($kepsek))->toBeTrue()
        ->and($policy->view($kepsek, $guruGrade))->toBeTrue()
        ->and($policy->create($kepsek))->toBeFalse()
        ->and($policy->update($kepsek, $guruGrade))->toBeFalse()
        ->and($policy->delete($kepsek, $guruGrade))->toBeFalse();

    // --- guru: can view/update only their own schedule's grades ---
    expect($policy->viewAny($guruUser))->toBeTrue()
        ->and($policy->view($guruUser, $guruGrade))->toBeTrue()
        ->and($policy->view($guruUser, $otherGuruGrade))->toBeFalse()
        ->and($policy->create($guruUser))->toBeTrue()
        ->and($policy->update($guruUser, $guruGrade))->toBeTrue()
        ->and($policy->update($guruUser, $otherGuruGrade))->toBeFalse()
        ->and($policy->delete($guruUser, $guruGrade))->toBeFalse()
        ->and($policy->delete($guruUser, $otherGuruGrade))->toBeFalse();

    // --- siswa_ortu: no access ---
    expect($policy->viewAny($siswa))->toBeFalse()
        ->and($policy->view($siswa, $guruGrade))->toBeFalse()
        ->and($policy->create($siswa))->toBeFalse()
        ->and($policy->update($siswa, $guruGrade))->toBeFalse()
        ->and($policy->delete($siswa, $guruGrade))->toBeFalse();
});

test('guru cannot access grades for another teachers schedule', function () {
    $policy = app(GradePolicy::class);

    $guruUser = User::factory()->asGuru()->create();
    $guruTeacher = Teacher::factory()->create(['user_id' => $guruUser->id]);

    $otherGuruUser = User::factory()->asGuru()->create();
    $otherGuruTeacher = Teacher::factory()->create(['user_id' => $otherGuruUser->id]);

    $otherGuruSchedule = Schedule::factory()->create(['teacher_id' => $otherGuruTeacher->id]);

    $otherGuruGrade = Grade::factory()->create([
        'subject_id' => $otherGuruSchedule->subject_id,
    ]);

    expect($policy->view($guruUser, $otherGuruGrade))->toBeFalse()
        ->and($policy->update($guruUser, $otherGuruGrade))->toBeFalse();
});

test('admin can access all grades regardless of teacher', function () {
    $policy = app(GradePolicy::class);

    $admin = User::factory()->asAdmin()->create();

    $guruUser = User::factory()->asGuru()->create();
    $guruTeacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
    $guruSchedule = Schedule::factory()->create(['teacher_id' => $guruTeacher->id]);

    $grade = Grade::factory()->create([
        'subject_id' => $guruSchedule->subject_id,
    ]);

    expect($policy->viewAny($admin))->toBeTrue()
        ->and($policy->view($admin, $grade))->toBeTrue()
        ->and($policy->create($admin))->toBeTrue()
        ->and($policy->update($admin, $grade))->toBeTrue()
        ->and($policy->delete($admin, $grade))->toBeTrue();
});
