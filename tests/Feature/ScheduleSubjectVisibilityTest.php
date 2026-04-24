<?php

use App\Models\Level;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

test('nama mata pelajaran lintas jenjang tidak tampil di tabel jadwal', function () {
    $admin = User::factory()->asAdmin()->create();

    $classLevel = Level::factory()->create(['name' => 'SD']);
    $subjectLevel = Level::factory()->create(['name' => 'SMP']);

    $teacher = Teacher::factory()->create();
    $schoolClass = SchoolClass::factory()->create([
        'level_id' => $classLevel->id,
        'teacher_id' => $teacher->id,
    ]);

    $subject = Subject::factory()->create([
        'level_id' => $subjectLevel->id,
        'name' => 'Bahasa Arab',
    ]);

    Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ]);

    $this->actingAs($admin)
        ->withSession(['active_academic_level_id' => $classLevel->id])
        ->get('/admin/academic/schedules')
        ->assertOk()
        ->assertDontSee('Bahasa Arab');
});
