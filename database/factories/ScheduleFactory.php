<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    public function definition(): array
    {
        $startHour = fake()->numberBetween(7, 14);
        $startTime = sprintf('%02d:00:00', $startHour);
        $endTime = sprintf('%02d:45:00', $startHour + 1);

        return [
            'class_id' => SchoolClass::factory(),
            'subject_id' => Subject::factory(),
            'teacher_id' => Teacher::factory(),
            'day_of_week' => fake()->numberBetween(1, 5), // 1=Senin, 5=Jumat
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }
}
