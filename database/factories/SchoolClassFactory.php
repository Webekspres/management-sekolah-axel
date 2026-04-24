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
        return [
            'level_id' => Level::query()->inRandomOrder()->value('id') ?? Level::factory(),
            'teacher_id' => Teacher::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'name' => function (array $attributes) {
                if (empty($attributes['level_id']) || is_object($attributes['level_id'])) {
                    return fake()->randomElement(['I', 'II', 'III', 'IV', 'V', 'VI']).' '.fake()->randomElement(['A', 'B', 'C', 'D']);
                }

                $level = Level::find($attributes['level_id']);
                $suffix = fake()->randomElement(['A', 'B', 'C', 'D']);

                if (! $level) {
                    return fake()->randomElement(['I', 'II', 'III', 'IV', 'V', 'VI']).' '.$suffix;
                }

                $className = match ($level->name) {
                    'SMA' => fake()->randomElement(['X', 'XI', 'XII']),
                    'SMP' => fake()->randomElement(['VII', 'VIII', 'IX']),
                    'SD' => fake()->randomElement(['I', 'II', 'III', 'IV', 'V', 'VI']),
                    default => fake()->randomElement(['I', 'II', 'VII', 'X']),
                };

                return "{$className} {$suffix}";
            },
        ];
    }
}
