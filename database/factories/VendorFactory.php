<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id'       => \App\Models\Branch::factory(),
            'owner_user_id'   => null,
            'name'            => fake()->company(),
            'code'            => strtoupper(fake()->unique()->bothify('VND-###')),
            'phone'           => fake()->phoneNumber(),
            'email'           => fake()->companyEmail(),
            'address'         => fake()->address(),
            'tin_number'      => fake()->numerify('###-###-###'),
            'commission_rate' => fake()->randomFloat(2, 1, 10),
            'status'          => 'active',
        ];
    }
}
