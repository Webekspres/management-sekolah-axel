<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SchoolClass>
 */
class ClassesFactory extends Factory
{
    protected $model = SchoolClass::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->regexify('[A-Z]{1}[0-9]{1}'),
            'level_id' => (string) Str::ulid(),
            'teacher_id' => (string) Str::ulid(),
            'academic_year_id' => (string) Str::ulid(),
        ];
    }
}
