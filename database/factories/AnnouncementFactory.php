<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        $role = fake()->randomElement(['super_admin', 'kepala_sekolah', 'guru', 'siswa_ortu']);

        return [
            'title' => fake()->sentence(5),
            'content' => fake()->paragraphs(3, true),
            'target_role' => [$role],
        ];
    }

    public function forGuru(): static
    {
        return $this->state(fn (array $attributes) => ['target_role' => ['guru']]);
    }

    public function forSiswa(): static
    {
        return $this->state(fn (array $attributes) => ['target_role' => ['siswa_ortu']]);
    }
}
