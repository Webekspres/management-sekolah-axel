<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LevelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Kelas '.fake()->randomElement(['VII', 'VIII', 'IX', 'X', 'XI', 'XII']),
            'default_spp' => fake()->randomElement([150000, 200000, 250000, 300000, 350000]),
        ];
    }
}
