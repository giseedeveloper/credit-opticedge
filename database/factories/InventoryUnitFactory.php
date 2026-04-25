<?php

namespace Database\Factories;

use App\Models\InventoryUnit;
use App\Models\PhoneModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryUnit>
 */
class InventoryUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone_model_id' => PhoneModel::factory(),
            'dealer_id' => null,
            'imei_1' => fake()->unique()->numerify('##################'),
            'imei_2' => null,
            'serial_number' => fake()->unique()->bothify('SN-#####??###'),
            'status' => 'available',
            'purchase_price' => fake()->numberBetween(150_000, 1_500_000),
            'received_at' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'extra_data' => null,
        ];
    }
}
