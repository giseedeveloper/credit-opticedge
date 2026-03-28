<?php

namespace Database\Factories;

use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $principal = fake()->numberBetween(200_000, 2_000_000);
        $rate = 20;
        $weeks = fake()->randomElement([4, 8, 12, 24]);
        $totalInterest = round($principal * ($rate / 100) * ($weeks / 52), 2);
        $totalPayable = $principal + $totalInterest;

        return [
            'customer_id'         => \App\Models\Customer::factory(),
            'inventory_unit_id'   => null,
            'vendor_id'           => null,
            'branch_id'           => null,
            'disbursed_by'        => null,
            'approved_by'         => null,
            'loan_number'         => 'LN-' . date('Ymd') . '-' . strtoupper(fake()->unique()->bothify('?????')),
            'principal_amount'    => $principal,
            'deposit_paid'        => 0,
            'interest_rate'       => $rate,
            'interest_type'       => 'flat',
            'total_debt'          => $totalPayable,
            'total_payable'       => $totalPayable,
            'amount_paid'         => 0,
            'remaining_balance'   => $totalPayable,
            'outstanding_balance' => $totalPayable,
            'penalty_amount'      => 0,
            'duration_weeks'      => $weeks,
            'grace_period_days'   => 0,
            'repayment_frequency' => 'weekly',
            'status'              => 'active',
            'disbursed_at'        => now()->toDateString(),
            'due_date'            => now()->addWeeks($weeks)->toDateString(),
            'completed_at'        => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed', 'outstanding_balance' => 0, 'completed_at' => now()->toDateString()]);
    }
}
