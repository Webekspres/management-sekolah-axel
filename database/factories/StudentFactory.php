<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->asSiswa(),
            'class_id' => SchoolClass::factory(),
            'nipd' => fake()->unique()->numerify('##########'),
            'nisn' => fake()->unique()->numerify('##########'),
            'nik' => fake()->unique()->numerify('################'),
            'kk_number' => fake()->numerify('################'),
            'birth_cert_number' => fake()->numerify('####/####/####'),
            'religion' => fake()->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']),
            'school_code' => fake()->numerify('#######'),
            'student_phone' => fake()->numerify('08##########'),
            'special_needs' => null,
            'house_number' => fake()->buildingNumber(),
            'rt' => fake()->numerify('###'),
            'rw' => fake()->numerify('###'),
            'village' => fake()->word(),
            'district' => fake()->word(),
            'city' => fake()->city(),
            'father_name' => fake()->name('male'),
            'father_phone' => fake()->numerify('08##########'),
            'mother_name' => fake()->name('female'),
            'mother_phone' => fake()->numerify('08##########'),
            'admission_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'origin_school' => 'SD '.fake()->company(),
            'diploma_date' => null,
            'diploma_number' => null,
            'custom_spp' => null,
        ];
    }

    public function withCustomSpp(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_spp' => $amount,
        ]);
    }
}
