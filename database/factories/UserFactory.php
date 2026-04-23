<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => fake()->randomElement(['super_admin', 'kepala_sekolah', 'guru', 'siswa_ortu']),
            'gender' => fake()->randomElement(['L', 'P']),
            'phone_number' => fake()->numerify('08##########'),
            'address_id' => null,
            'place_of_birth' => fake()->city(),
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-15 years')->format('Y-m-d'),
            'is_active' => true,
            'city_id' => null,
            'address_detail' => fake()->boolean(40) ? fake()->sentence() : null,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function asGuru(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'guru',
        ]);
    }

    public function asSiswa(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'siswa_ortu',
        ]);
    }

    public function asAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'super_admin',
        ]);
    }

    public function asKepalaSekolah(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'kepala_sekolah',
        ]);
    }

    public function withAddress(): static
    {
        return $this->state(function (array $attributes): array {
            $address = Address::factory()->create();

            return [
                'address_id' => $address->id,
                'city_id' => $address->city_id,
                'place_of_birth' => $address->city?->name ?? $attributes['place_of_birth'],
            ];
        });
    }
}
