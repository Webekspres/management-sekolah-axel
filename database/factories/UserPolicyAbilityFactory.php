<?php

namespace Database\Factories;

use App\Models\AccessPolicy;
use App\Models\User;
use App\Models\UserPolicyAbility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPolicyAbility>
 */
class UserPolicyAbilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'access_policy_id' => AccessPolicy::factory(),
            'ability' => $this->faker->randomElement(['create', 'read', 'update', 'delete', 'viewAny']),
            'is_inherited' => false,
            'source_role' => null,
            'granted_by_user_id' => User::factory(),
        ];
    }
}
