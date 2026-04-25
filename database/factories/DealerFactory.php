<?php

namespace Database\Factories;

use App\Models\Dealer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dealer>
 */
class DealerFactory extends Factory
{
    /**
     * @var class-string<Dealer>
     */
    protected $model = Dealer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_user_id' => null,
            'name' => fake()->company(),
            'code' => strtoupper(fake()->unique()->bothify('DLR-###')),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'tin_number' => fake()->numerify('###-###-###'),
            'commission_rate' => fake()->randomFloat(2, 1, 10),
            'status' => 'active',
        ];
    }
}
