<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class GradeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'subject_id' => Subject::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'grade_type' => fake()->randomElement(['UH1', 'UH2', 'UTS', 'UAS', 'Tugas']),
            'score' => fake()->randomFloat(2, 40, 100),
        ];
    }
}
