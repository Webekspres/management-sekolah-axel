<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonalityScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'kedisiplinan' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'kerapihan' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'kerajinan' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'kesopanan' => fake()->randomElement(['A', 'B', 'C', 'D']),
        ];
    }
}
