<?php

use App\Models\LessonPlan;
use App\Models\LessonPlanMaterial;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

test('observer membuat notifikasi saat LessonPlanMaterial dibuat', function (): void {
    $schoolClass = SchoolClass::factory()->create();
    $student = Student::factory()->create(['class_id' => $schoolClass->id]);
    $lessonPlan = LessonPlan::factory()->create(['class_id' => $schoolClass->id]);

    LessonPlanMaterial::factory()->create(['lesson_plan_id' => $lessonPlan->id]);

    expect($student->user->notifications()->count())->toBe(1);
});

test('exception dalam service tidak menggagalkan pembuatan LessonPlanMaterial', function (): void {
    Log::shouldReceive('error')->once();

    $this->mock(NotificationService::class, function ($mock): void {
        $mock->shouldReceive('createForLessonPlanMaterial')
            ->once()
            ->andThrow(new RuntimeException('Service error'));
    });

    $lessonPlan = LessonPlan::factory()->create();
    $material = LessonPlanMaterial::factory()->create(['lesson_plan_id' => $lessonPlan->id]);

    expect(LessonPlanMaterial::find($material->id))->not->toBeNull();
});
