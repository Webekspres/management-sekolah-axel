<?php

use App\Models\LessonPlan;
use App\Models\LessonPlanMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('menghapus LessonPlan menghapus semua materials beserta file fisiknya', function (): void {
    // Feature: rpp-materi-upload, Property 9: Penghapusan LessonPlan menghapus semua materialnya
    Storage::fake('public');

    $count = fake()->numberBetween(1, 5);
    $lessonPlan = LessonPlan::factory()
        ->has(LessonPlanMaterial::factory()->withFakeFile()->count($count), 'materials')
        ->create();

    $filePaths = $lessonPlan->materials->pluck('file_path')->all();

    expect($filePaths)->toHaveCount($count);
    foreach ($filePaths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeTrue();
    }

    $lessonPlan->delete();

    expect(LessonPlanMaterial::where('lesson_plan_id', $lessonPlan->id)->count())->toBe(0);
    foreach ($filePaths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeFalse();
    }
});

it('menghapus LessonPlan tanpa materials tidak throw exception', function (): void {
    $lessonPlan = LessonPlan::factory()->create();

    expect(fn () => $lessonPlan->delete())->not->toThrow(Throwable::class);
    expect(LessonPlan::find($lessonPlan->id))->toBeNull();
});

it('memiliki relasi materials ke LessonPlanMaterial', function (): void {
    $lessonPlan = LessonPlan::factory()->create();
    $materials = LessonPlanMaterial::factory()->count(3)->create([
        'lesson_plan_id' => $lessonPlan->id,
    ]);

    expect($lessonPlan->materials)->toHaveCount(3);
    expect($lessonPlan->materials->first())->toBeInstanceOf(LessonPlanMaterial::class);
});
