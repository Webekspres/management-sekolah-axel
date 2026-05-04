<?php

use App\Models\Rapor;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\RaporPolicy;

test('rapor policy finalize: wali kelas can finalize rapor for their own class', function () {
    $policy = app(RaporPolicy::class);

    $waliKelasUser = User::factory()->asGuru()->create();
    $waliKelasTeacher = Teacher::factory()->create(['user_id' => $waliKelasUser->id]);

    $schoolClass = SchoolClass::factory()->create(['teacher_id' => $waliKelasTeacher->id]);
    $student = Student::factory()->create(['class_id' => $schoolClass->id]);
    $rapor = Rapor::factory()->create(['student_id' => $student->id]);

    expect($policy->finalize($waliKelasUser, $rapor))->toBeTrue();
});

test('rapor policy finalize: other guru cannot finalize rapor for another class', function () {
    $policy = app(RaporPolicy::class);

    $waliKelasUser = User::factory()->asGuru()->create();
    $waliKelasTeacher = Teacher::factory()->create(['user_id' => $waliKelasUser->id]);

    $otherGuruUser = User::factory()->asGuru()->create();
    $otherGuruTeacher = Teacher::factory()->create(['user_id' => $otherGuruUser->id]);

    $schoolClass = SchoolClass::factory()->create(['teacher_id' => $waliKelasTeacher->id]);
    $student = Student::factory()->create(['class_id' => $schoolClass->id]);
    $rapor = Rapor::factory()->create(['student_id' => $student->id]);

    expect($policy->finalize($otherGuruUser, $rapor))->toBeFalse();
});

test('rapor policy finalize: super_admin can finalize any rapor', function () {
    $policy = app(RaporPolicy::class);

    $admin = User::factory()->asAdmin()->create();
    $rapor = Rapor::factory()->create();

    expect($policy->finalize($admin, $rapor))->toBeTrue();
});

test('rapor policy approve: kepala_sekolah can approve finalized rapor', function () {
    $policy = app(RaporPolicy::class);

    $kepsek = User::factory()->asKepalaSekolah()->create();
    $admin = User::factory()->asAdmin()->create();
    $guruUser = User::factory()->asGuru()->create();
    $siswa = User::factory()->asSiswa()->create();

    $rapor = Rapor::factory()->finalized()->create();

    expect($policy->approve($kepsek, $rapor))->toBeTrue()
        ->and($policy->approve($admin, $rapor))->toBeTrue()
        ->and($policy->approve($guruUser, $rapor))->toBeFalse()
        ->and($policy->approve($siswa, $rapor))->toBeFalse();
});

test('rapor policy reject: kepala_sekolah can reject finalized rapor', function () {
    $policy = app(RaporPolicy::class);

    $kepsek = User::factory()->asKepalaSekolah()->create();
    $admin = User::factory()->asAdmin()->create();
    $guruUser = User::factory()->asGuru()->create();
    $siswa = User::factory()->asSiswa()->create();

    $rapor = Rapor::factory()->finalized()->create();

    expect($policy->reject($kepsek, $rapor))->toBeTrue()
        ->and($policy->reject($admin, $rapor))->toBeTrue()
        ->and($policy->reject($guruUser, $rapor))->toBeFalse()
        ->and($policy->reject($siswa, $rapor))->toBeFalse();
});

test('rapor policy download: siswa can only download their own approved rapor', function () {
    $policy = app(RaporPolicy::class);

    $siswaUser = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $siswaUser->id]);

    $otherSiswaUser = User::factory()->asSiswa()->create();
    $otherStudent = Student::factory()->create(['user_id' => $otherSiswaUser->id]);

    $approvedRapor = Rapor::factory()->approved()->create(['student_id' => $student->id]);
    $draftRapor = Rapor::factory()->create(['student_id' => $student->id, 'status' => 'DRAFT']);
    $finalizedRapor = Rapor::factory()->finalized()->create(['student_id' => $student->id]);
    $otherApprovedRapor = Rapor::factory()->approved()->create(['student_id' => $otherStudent->id]);

    // Siswa can download their own APPROVED rapor
    expect($policy->download($siswaUser, $approvedRapor))->toBeTrue();

    // Siswa cannot download DRAFT or FINALIZED rapor
    expect($policy->download($siswaUser, $draftRapor))->toBeFalse()
        ->and($policy->download($siswaUser, $finalizedRapor))->toBeFalse();

    // Siswa cannot download another student's rapor
    expect($policy->download($siswaUser, $otherApprovedRapor))->toBeFalse();
});

test('rapor policy download: super_admin can download any rapor', function () {
    $policy = app(RaporPolicy::class);

    $admin = User::factory()->asAdmin()->create();
    $rapor = Rapor::factory()->approved()->create();

    expect($policy->download($admin, $rapor))->toBeTrue();
});
