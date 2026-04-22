<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'subject_id' => Subject::factory(),
            'topic' => fake()->sentence(4),
            'file_path' => 'lesson_plans/'.fake()->uuid().'.pdf',
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

    public function revised(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'REVISED',
            'revision_note' => fake()->sentence(),
        ]);
    }
}
