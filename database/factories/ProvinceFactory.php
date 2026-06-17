<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProvinceFactory extends Factory
{
    public function definition(): array
    {
        $provinces = [
            'DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'Jawa Timur', 'Banten',
            'Bali', 'Sumatera Utara', 'Sumatera Barat', 'Sumatera Selatan',
            'Kalimantan Barat', 'Kalimantan Timur', 'Sulawesi Selatan',
            'Nusa Tenggara Barat', 'Papua', 'Aceh',
        ];

        return [
            'name' => fake()->unique()->randomElement($provinces),
        ];
    }
}
