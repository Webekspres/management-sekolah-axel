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
            'file_path' => null,
            'status' => 'DRAFT',
            'approved_at' => null,
            'rejection_note' => null,
        ];
    }

    public function finalized(): static
    {
        return $this->state(['status' => 'FINALIZED']);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => 'APPROVED',
            'approved_at' => now(),
            'file_path' => 'rapors/'.fake()->uuid().'.pdf',
        ]);
    }
}
