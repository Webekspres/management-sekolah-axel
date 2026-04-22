<?php

namespace Database\Factories;

use App\Models\LessonPlan;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class KbmFactory extends Factory
{
    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'lesson_plan_id' => LessonPlan::factory()->approved(),
            'date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'process_note' => fake()->paragraph(),
            'problem_note' => fake()->optional(0.4)->sentence(),
            'solution_note' => fake()->optional(0.4)->sentence(),
            'documentation_path' => fake()->optional(0.6)->filePath(),
            'status' => fake()->randomElement(['DRAFT', 'PENDING', 'REVISED', 'APPROVED']),
            'revision_note' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'APPROVED',
            'revision_note' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PENDING',
            'revision_note' => null,
        ]);
    }

    public function revised(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'REVISED',
            'revision_note' => fake()->sentence(),
        ]);
    }
}
