<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-'.strtoupper(fake()->bothify('####-????')),
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'amount' => fake()->randomElement([150000, 200000, 250000, 300000, 350000]),
            'description' => 'SPP '.fake()->monthName().' '.fake()->year(),
            'status' => fake()->randomElement(['UNPAID', 'PENDING', 'PAID', 'FAILED']),
            'due_date' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
        ];
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'UNPAID']);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'PAID']);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'UNPAID',
            'due_date' => fake()->dateTimeBetween('-3 months', '-1 day')->format('Y-m-d'),
        ]);
    }
}
