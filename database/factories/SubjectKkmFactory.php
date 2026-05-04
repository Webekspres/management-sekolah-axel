<?php

namespace Database\Factories;

use App\Models\Level;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectKkmFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'level_id' => Level::factory(),
            'kkm' => fake()->randomFloat(2, 60, 80),
        ];
    }
}
