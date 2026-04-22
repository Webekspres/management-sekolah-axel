<?php

namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    public function definition(): array
    {
        $subjects = [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'IPA', 'IPS',
            'Pendidikan Agama Islam', 'PKN', 'Seni Budaya', 'Penjaskes',
            'Prakarya', 'Informatika', 'Fisika', 'Kimia', 'Biologi',
            'Sejarah Indonesia', 'Geografi', 'Ekonomi', 'Sosiologi',
        ];

        return [
            'name' => fake()->randomElement($subjects),
            'level_id' => Level::factory(),
        ];
    }
}
