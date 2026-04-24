<?php

namespace Database\Factories;

use App\Models\AccessPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessPolicy>
 */
class AccessPolicyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->word();

        return [
            'code' => $name.'_policy_'.$this->faker->randomNumber(3),
            'name' => ucfirst($name).' Policy',
            'description' => $this->faker->sentence(),
            'target_model' => 'App\\Models\\'.class_basename($this->faker->randomElement([
                'App\\Models\\Announcement',
                'App\\Models\\LessonPlan',
                'App\\Models\\Kbm',
            ])),
            'abilities' => ['create', 'read', 'update', 'delete'],
            'permanent_roles' => ['super_admin'],
            'is_active' => true,
        ];
    }
}
