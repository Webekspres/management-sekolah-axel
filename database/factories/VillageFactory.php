<?php

namespace Database\Factories;

use App\Models\SubDistrict;
use Illuminate\Database\Eloquent\Factories\Factory;

class VillageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sub_district_id' => SubDistrict::factory(),
            'name' => 'Kel. '.fake()->word(),
        ];
    }
}
