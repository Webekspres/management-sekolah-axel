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
        return [
            'province_id' => Province::factory(),
            'city_id' => City::factory(),
            'sub_district_id' => SubDistrict::factory(),
            'village_id' => Village::factory(),
            'street' => fake()->streetAddress(),
            'postal_code' => fake()->numerify('#####'),
        ];
    }
}
