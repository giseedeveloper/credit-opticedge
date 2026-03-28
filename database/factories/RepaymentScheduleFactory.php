<?php

namespace Database\Factories;

use App\Models\RepaymentSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepaymentSchedule>
 */
class RepaymentScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amountDue = fake()->numberBetween(50_000, 200_000);

        return [
            'loan_id'              => \App\Models\Loan::factory(),
            'installment_number'   => 1,
            'amount_due'           => $amountDue,
            'principal_component'  => round($amountDue * 0.85, 2),
            'interest_component'   => round($amountDue * 0.15, 2),
            'penalty_component'    => 0,
            'amount_paid'          => 0,
            'balance_remaining'    => $amountDue,
            'due_date'             => now()->addWeek()->toDateString(),
            'paid_at'              => null,
            'status'               => 'pending',
            'days_overdue'         => 0,
        ];
    }

    public function overdue(): static
    {
        return $this->state([
            'due_date'     => now()->subDays(10)->toDateString(),
            'status'       => 'overdue',
            'days_overdue' => 10,
        ]);
    }
}
