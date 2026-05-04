<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class LearningAchievementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'subject_id' => Subject::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'topic_coverage' => fake()->optional()->paragraph(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
