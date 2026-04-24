<?php

use App\Filament\Kepsek\Resources\Kbms\Pages\ListKbms;
use App\Filament\Kepsek\Resources\LessonPlans\Pages\ListLessonPlans;
use App\Models\Kbm;
use App\Models\LessonPlan;
use App\Models\Schedule;
use App\Models\Teacher;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->asKepalaSekolah()->create());
    Filament::setCurrentPanel(Filament::getPanel('kepsek'));
});

test('kepsek dapat membuka detail kbm tanpa error', function () {
    $teacher = Teacher::factory()->create();
    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $teacher->id,
        'status' => 'PENDING',
    ]);
    $schedule = Schedule::factory()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $lessonPlan->subject_id,
    ]);
    $kbm = Kbm::factory()->create([
        'schedule_id' => $schedule->id,
        'lesson_plan_id' => $lessonPlan->id,
        'documentation_path' => 'kbm/docs/laporan-kbm.pdf',
        'status' => 'PENDING',
    ]);

    Livewire::test(ListKbms::class)
        ->mountTableAction('detail', $kbm->getKey())
        ->assertHasNoTableActionErrors();
});

test('kepsek dapat membuka detail rpp tanpa error', function () {
    $lessonPlan = LessonPlan::factory()->create([
        'status' => 'PENDING',
        'file_path' => 'lesson_plans/rpp-bab-1.pdf',
    ]);

    Livewire::test(ListLessonPlans::class)
        ->mountTableAction('detail', $lessonPlan->getKey())
        ->assertHasNoTableActionErrors();
});
