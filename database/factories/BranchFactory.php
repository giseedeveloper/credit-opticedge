<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'           => fake()->city(),
            'code'           => strtoupper(fake()->unique()->bothify('BR-###')),
            'region'         => fake()->state(),
            'address'        => fake()->address(),
            'phone'          => fake()->phoneNumber(),
            'is_headquarter' => false,
            'is_active'      => true,
        ];
    }

    public function headquarter(): static
    {
        return $this->state(['is_headquarter' => true]);
    }
}
