<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    public function definition(): array
    {
        $province = Province::query()->inRandomOrder()->first();

        if ($province === null) {
            return [
                'province_id' => Province::factory(),
                'city_id' => City::factory(),
                'sub_district_id' => SubDistrict::factory(),
                'village_id' => Village::factory(),
                'street' => fake()->streetAddress(),
                'postal_code' => fake()->numerify('#####'),
            ];
        }

        $city = City::query()
            ->where('province_id', $province->id)
            ->inRandomOrder()
            ->first();

        if ($city === null) {
            $city = City::factory()->create(['province_id' => $province->id]);
        }

        $subDistrict = SubDistrict::query()
            ->where('city_id', $city->id)
            ->inRandomOrder()
            ->first();

        if ($subDistrict === null) {
            $subDistrict = SubDistrict::factory()->create(['city_id' => $city->id]);
        }

        $village = Village::query()
            ->where('sub_district_id', $subDistrict->id)
            ->inRandomOrder()
            ->first();

        if ($village === null) {
            $village = Village::factory()->create(['sub_district_id' => $subDistrict->id]);
        }

        return [
            'province_id' => $province->id,
            'city_id' => $city->id,
            'sub_district_id' => $subDistrict->id,
            'village_id' => $village->id,
            'street' => fake()->streetAddress(),
            'postal_code' => fake()->numerify('#####'),
        ];
    }
}
