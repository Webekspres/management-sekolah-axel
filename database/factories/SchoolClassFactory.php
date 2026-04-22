<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolClassFactory extends Factory
{
    public function definition(): array
    {
        $level = fake()->randomElement(['VII', 'VIII', 'IX', 'X', 'XI', 'XII']);
        $suffix = fake()->randomElement(['A', 'B', 'C', 'D']);

        return [
            'name' => "{$level} {$suffix}",
            'level_id' => Level::factory(),
            'teacher_id' => Teacher::factory(),
            'academic_year_id' => AcademicYear::factory(),
        ];
    }
}
