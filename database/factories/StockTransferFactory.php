<?php

namespace Database\Factories;

use App\Models\Dealer;
use App\Models\InventoryUnit;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_unit_id' => InventoryUnit::factory(),
            'from_type' => Dealer::class,
            'from_id' => Dealer::factory(),
            'to_type' => Dealer::class,
            'to_id' => Dealer::factory(),
            'transferred_by' => User::factory(),
            'reference' => 'TRF-'.strtoupper(fake()->unique()->bothify('######')),
            'status' => 'pending',
            'notes' => null,
            'shipped_at' => null,
            'received_at' => null,
        ];
    }
}
