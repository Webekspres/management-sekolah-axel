<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        $entities = ['User', 'Student', 'Teacher', 'Invoice', 'Payment', 'Grade', 'Attendance', 'LessonPlan', 'Kbm'];

        $logNames = ['auth', 'spp', 'absensi', 'rpp', 'rapor', 'jadwal', 'user', 'siswa', 'guru', 'general'];

        return [
            'user_id' => fake()->boolean(80) ? User::factory() : null,
            'action' => fake()->randomElement(['created', 'updated', 'deleted', 'login', 'logout', 'approved', 'downloaded', 'generated']),
            'entity_type' => fake()->randomElement($entities),
            'entity_id' => fake()->uuid(),
            'log_name' => fake()->randomElement($logNames),
            'description' => fake()->sentence(),
            'properties' => null,
            'created_at' => now(),
        ];
    }
}
