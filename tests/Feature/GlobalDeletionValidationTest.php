<?php

use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Validation\ValidationException;

test('subject tidak bisa dihapus jika masih direferensikan tabel lain', function () {
    $teacher = Teacher::factory()->create();
    $schoolClass = SchoolClass::factory()->create([
        'teacher_id' => $teacher->id,
    ]);

    $subject = Subject::factory()->create([
        'level_id' => $schoolClass->level_id,
    ]);

    Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ]);

    expect(fn () => $subject->delete())
        ->toThrow(ValidationException::class);
});
