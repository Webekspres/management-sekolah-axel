<?php

use App\Filament\Student\Widgets\SiswaOrtuOverviewStats;
use App\Models\LessonPlan;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('widget hanya menghitung rpp dengan status approved untuk siswa terkait', function () {
    $student = Student::factory()->create();
    $teacher = Teacher::factory()->create();
    $level = Level::factory()->create(['name' => 'SD']);
    $subject = Subject::factory()->create([
        'level_id' => $level->id,
    ]);

    $this->actingAs($student->user);
    Filament::setCurrentPanel(Filament::getPanel('student'));

    LessonPlan::factory()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'class_id' => $student->class_id,
        'status' => 'APPROVED',
        'implementation_date' => now()->toDateString(),
    ]);

    LessonPlan::factory()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'class_id' => $student->class_id,
        'status' => 'PENDING',
        'implementation_date' => now()->toDateString(),
    ]);

    LessonPlan::factory()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'class_id' => SchoolClass::factory(),
        'status' => 'APPROVED',
        'implementation_date' => now()->toDateString(),
    ]);

    Livewire::test(SiswaOrtuOverviewStats::class)
        ->assertSee('RPP Approved Minggu Ini')
        ->assertSee('1')
        ->assertSee('Hanya RPP berstatus APPROVED');
});
