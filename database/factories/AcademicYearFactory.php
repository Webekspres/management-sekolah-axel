<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    public function definition(): array
    {
        $year = fake()->numberBetween(2020, 2026);
        $next = $year + 1;

        return [
            'name' => "{$year}/{$next}",
            'semester' => fake()->randomElement(['Ganjil', 'Genap']),
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
