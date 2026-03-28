<?php

namespace Database\Factories;

use App\Models\Verification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Verification>
 */
class VerificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id'      => \App\Models\Customer::factory(),
            'reviewed_by'      => null,
            'type'             => 'kyc',
            'status'           => 'pending',
            'notes'            => null,
            'rejection_reason' => null,
            'reviewed_at'      => null,
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status'      => 'approved',
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status'           => 'rejected',
            'rejection_reason' => fake()->sentence(),
            'reviewed_at'      => now(),
        ]);
    }
}
