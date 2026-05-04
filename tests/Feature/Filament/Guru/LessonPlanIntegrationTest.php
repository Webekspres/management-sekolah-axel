<?php

/**
 * Integrasi ringan: verifikasi policy enforcement per-status untuk guru.
 * Kontrak policy matrix lengkap hidup di `tests/Feature/Policies/AcademicWorkflowPolicyTest.php`.
 */

use App\Models\LessonPlan;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\LessonPlanPolicy;

beforeEach(function () {
    $this->policy = app(LessonPlanPolicy::class);
    $this->guruUser = User::factory()->asGuru()->create();
    $this->teacher = Teacher::factory()->create(['user_id' => $this->guruUser->id]);
});

test('4.2 guru tidak dapat mengedit RPP berstatus PENDING', function () {
    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $this->teacher->id,
        'status' => 'PENDING',
    ]);

    expect($this->policy->update($this->guruUser, $lessonPlan))->toBeFalse();
});

test('4.2 guru tidak dapat mengedit RPP berstatus APPROVED', function () {
    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $this->teacher->id,
        'status' => 'APPROVED',
    ]);

    expect($this->policy->update($this->guruUser, $lessonPlan))->toBeFalse();
});

test('4.2 guru dapat mengedit RPP berstatus DRAFT dan REVISED', function () {
    $draft = LessonPlan::factory()->create([
        'teacher_id' => $this->teacher->id,
        'status' => 'DRAFT',
    ]);

    $revised = LessonPlan::factory()->revised()->create([
        'teacher_id' => $this->teacher->id,
    ]);

    expect($this->policy->update($this->guruUser, $draft))->toBeTrue()
        ->and($this->policy->update($this->guruUser, $revised))->toBeTrue();
});
