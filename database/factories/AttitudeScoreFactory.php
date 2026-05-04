<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttitudeScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'aspect' => fake()->randomElement(['Spiritual', 'Sosial']),
            'score' => fake()->randomFloat(2, 60, 100),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
