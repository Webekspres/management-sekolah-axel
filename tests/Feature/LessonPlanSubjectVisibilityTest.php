<?php

use App\Models\LessonPlan;
use App\Models\Level;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

test('data mata pelajaran tetap muncul di tabel approval rpp', function () {
    $admin = User::factory()->asAdmin()->create();

    $activeLevel = Level::factory()->create(['name' => 'SD']);
    $otherLevel = Level::factory()->create(['name' => 'SMP']);

    $teacher = Teacher::factory()->create();
    $subject = Subject::factory()->create([
        'level_id' => $otherLevel->id,
        'name' => 'Pendidikan Pancasila',
    ]);

    LessonPlan::factory()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'status' => 'PENDING',
    ]);

    $this->actingAs($admin)
        ->withSession(['active_academic_level_id' => $activeLevel->id])
        ->get('/admin/academic/lesson-plans')
        ->assertOk()
        ->assertSee('Pendidikan Pancasila');
});
