<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_id'               => null,
            'repayment_schedule_id' => null,
            'customer_id'           => null,
            'recorded_by'           => null,
            'reference'             => 'TXN-' . strtoupper(fake()->unique()->bothify('????????????')),
            'type'                  => 'repayment',
            'entry_type'            => 'credit',
            'amount'                => fake()->numberBetween(50_000, 500_000),
            'channel'               => fake()->randomElement(['cash', 'mobile_money', 'bank']),
            'external_reference'    => null,
            'description'           => fake()->sentence(),
            'meta'                  => null,
            'transacted_at'         => now(),
        ];
    }
}
