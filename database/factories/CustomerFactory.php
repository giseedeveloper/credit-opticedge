<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Dealer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dealer_id' => Dealer::factory(),
            'registered_by' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'middle_name' => null,
            'phone' => fake()->unique()->numerify('07########'),
            'alt_phone' => null,
            'email' => fake()->unique()->safeEmail(),
            'nida_number' => fake()->unique()->numerify('####################'),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'occupation' => fake()->jobTitle(),
            'employer' => fake()->company(),
            'monthly_income' => fake()->numberBetween(200_000, 3_000_000),
            'address' => fake()->address(),
            'region' => fake()->state(),
            'district' => fake()->city(),
            'kyc_status' => 'pending',
            'credit_status' => 'eligible',
        ];
    }

    public function kycApproved(): static
    {
        return $this->state(['kyc_status' => 'approved']);
    }
}
