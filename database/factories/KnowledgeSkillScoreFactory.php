<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class KnowledgeSkillScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'subject_id' => Subject::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'knowledge_score' => fake()->optional(0.8)->randomFloat(2, 60, 100),
            'knowledge_predicate' => fake()->optional(0.8)->randomElement(['A', 'B', 'C', 'D']),
            'knowledge_description' => fake()->optional()->sentence(),
            'skill_score' => fake()->optional(0.8)->randomFloat(2, 60, 100),
            'skill_predicate' => fake()->optional(0.8)->randomElement(['A', 'B', 'C', 'D']),
            'skill_description' => fake()->optional()->sentence(),
        ];
    }
}
