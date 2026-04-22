<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class RaporFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'file_path' => 'rapors/'.fake()->uuid().'.pdf',
        ];
    }
}
