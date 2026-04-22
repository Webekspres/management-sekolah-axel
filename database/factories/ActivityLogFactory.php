<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        $entities = ['User', 'Student', 'Teacher', 'Invoice', 'Payment', 'Grade', 'Attendance', 'LessonPlan', 'Kbm'];

        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['create', 'update', 'delete', 'login', 'logout', 'approve', 'reject']),
            'entity_type' => fake()->randomElement($entities),
            'entity_id' => fake()->uuid(),
            'description' => fake()->sentence(),
        ];
    }
}
