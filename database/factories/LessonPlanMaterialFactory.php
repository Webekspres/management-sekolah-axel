<?php

namespace Database\Factories;

use App\Models\LessonPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

class LessonPlanMaterialFactory extends Factory
{
    public function definition(): array
    {
        $filename = fake()->uuid().'.pdf';

        return [
            'lesson_plan_id' => LessonPlan::factory(),
            'file_path' => 'lesson-plan-materials/'.$filename,
            'original_filename' => fake()->word().'.pdf',
        ];
    }

    /**
     * State that creates a real dummy file in storage for testing file deletion.
     */
    public function withFakeFile(): static
    {
        return $this->afterCreating(function ($material): void {
            Storage::disk('public')->put($material->file_path, 'dummy content');
        });
    }
}
