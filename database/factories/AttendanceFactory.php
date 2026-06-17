<?php

namespace Database\Factories;

use App\Models\Kbm;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kbm_id' => Kbm::factory(),
            'student_id' => Student::factory(),
            'status' => fake()->randomElement(['HADIR', 'SAKIT', 'IZIN', 'ALPA']),
        ];
    }

    public function hadir(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'HADIR']);
    }

    public function sakit(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'SAKIT']);
    }

    public function izin(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'IZIN']);
    }

    public function alpa(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'ALPA']);
    }
}
