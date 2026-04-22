<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'amount_paid' => fake()->randomElement([150000, 200000, 250000, 300000, 350000]),
            'payment_method' => fake()->randomElement(['transfer', 'cash', 'qris', 'va_bni', 'va_bca', 'va_mandiri']),
            'pg_transaction_id' => fake()->unique()->uuid(),
            'status' => fake()->randomElement(['UNPAID', 'PENDING', 'PAID', 'FAILED']),
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PAID',
            'paid_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PENDING',
            'paid_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'FAILED',
            'paid_at' => null,
        ]);
    }
}
