<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'is_read' => fake()->boolean(20),
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => ['is_read' => true]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => ['is_read' => false]);
    }
}
