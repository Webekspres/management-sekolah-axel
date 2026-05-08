<?php

use App\Models\LessonPlan;
use App\Models\LessonPlanMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// Feature: rpp-materi-upload, Property 8: Penghapusan material menghapus file fisik
it('menghapus material selalu menghapus file fisik dari storage', function (): void {
    // Validates: Requirements 5.1, 5.3
    Storage::fake('public');

    for ($i = 0; $i < 100; $i++) {
        $material = LessonPlanMaterial::factory()->withFakeFile()->create();
        $filePath = $material->file_path;

        expect(Storage::disk('public')->exists($filePath))->toBeTrue();

        $material->delete();

        expect(Storage::disk('public')->exists($filePath))->toBeFalse();
    }
});

// Feature: rpp-materi-upload, Property 8: Penghapusan material menghapus file fisik
it('menghapus material tidak throw exception jika file fisik tidak ada', function (): void {
    // Validates: Requirements 5.3
    Storage::fake('public');

    for ($i = 0; $i < 100; $i++) {
        // Buat material tanpa membuat file fisik di storage
        $material = LessonPlanMaterial::factory()->create();

        expect(Storage::disk('public')->exists($material->file_path))->toBeFalse();

        // Penghapusan record harus berhasil tanpa exception
        expect(fn () => $material->delete())->not->toThrow(Throwable::class);

        expect(LessonPlanMaterial::find($material->id))->toBeNull();
    }
});

// Feature: rpp-materi-upload, Property 9: Penghapusan LessonPlan menghapus semua materialnya
it('menghapus LessonPlan menghapus semua materials beserta file fisiknya', function (): void {
    // Validates: Requirements 5.2
    Storage::fake('public');

    for ($i = 0; $i < 100; $i++) {
        $count = fake()->numberBetween(1, 5);

        $lessonPlan = LessonPlan::factory()
            ->has(LessonPlanMaterial::factory()->withFakeFile()->count($count), 'materials')
            ->create();

        $materialIds = $lessonPlan->materials->pluck('id')->all();
        $filePaths = $lessonPlan->materials->pluck('file_path')->all();

        expect($filePaths)->toHaveCount($count);

        foreach ($filePaths as $path) {
            expect(Storage::disk('public')->exists($path))->toBeTrue();
        }

        $lessonPlan->delete();

        // Semua record LessonPlanMaterial harus terhapus dari database
        expect(LessonPlanMaterial::where('lesson_plan_id', $lessonPlan->id)->count())->toBe(0);

        // Semua file fisik harus terhapus dari storage
        foreach ($filePaths as $path) {
            expect(Storage::disk('public')->exists($path))->toBeFalse();
        }
    }
});
