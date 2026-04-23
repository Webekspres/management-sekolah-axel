<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Province;
use App\Models\SchoolClass;
use App\Models\SubDistrict;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        $regionData = $this->resolveRegionData();

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
            'village' => $regionData['village'],
            'district' => $regionData['district'],
            'city' => $regionData['city'],
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

    /**
     * Resolve region names from seeded wilayah data.
     *
     * @return array{village: string|null, district: string|null, city: string|null}
     */
    private function resolveRegionData(): array
    {
        $province = Province::query()->inRandomOrder()->first();

        if (! $province) {
            return [
                'village' => fake()->word(),
                'district' => fake()->word(),
                'city' => fake()->city(),
            ];
        }

        $city = City::query()
            ->where('province_id', $province->id)
            ->inRandomOrder()
            ->first();

        if (! $city) {
            return [
                'village' => fake()->word(),
                'district' => fake()->word(),
                'city' => fake()->city(),
            ];
        }

        $subDistrict = SubDistrict::query()
            ->where('city_id', $city->id)
            ->inRandomOrder()
            ->first();

        $village = $subDistrict
            ? Village::query()->where('sub_district_id', $subDistrict->id)->inRandomOrder()->first()
            : null;

        return [
            'village' => $village?->name,
            'district' => $subDistrict?->name,
            'city' => $city->name,
        ];
    }
}
