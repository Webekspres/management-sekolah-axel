<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->asGuru(),
            'nip' => fake()->numerify('##################'),
            'employment_status' => fake()->randomElement(['staff_tu', 'guru_kelas', 'other']),
        ];
    }
}
