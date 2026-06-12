<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $dueDate = fake()->dateTimeBetween('now', '+2 months');

        return [
            'invoice_number' => 'INV-'.strtoupper(fake()->bothify('####-????')),
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'amount' => fake()->randomElement([150000, 200000, 250000, 300000, 350000]),
            'description' => 'SPP '.fake()->monthName().' '.fake()->year(),
            'billing_period' => Invoice::billingPeriodFromDate(Carbon::parse($dueDate)),
            'status' => fake()->randomElement(['UNPAID', 'PENDING', 'PAID', 'FAILED']),
            'due_date' => $dueDate->format('Y-m-d'),
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
        $dueDate = fake()->dateTimeBetween('-3 months', '-1 day');

        return $this->state(fn (array $attributes) => [
            'status' => 'UNPAID',
            'due_date' => $dueDate->format('Y-m-d'),
            'billing_period' => Invoice::billingPeriodFromDate(Carbon::parse($dueDate)),
        ]);
    }
}
