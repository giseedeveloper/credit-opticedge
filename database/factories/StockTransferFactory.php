<?php

namespace Database\Factories;

use App\Models\StockTransfer;
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
            'inventory_unit_id' => \App\Models\InventoryUnit::factory(),
            'from_type'         => \App\Models\Branch::class,
            'from_id'           => \App\Models\Branch::factory(),
            'to_type'           => \App\Models\Vendor::class,
            'to_id'             => \App\Models\Vendor::factory(),
            'transferred_by'    => \App\Models\User::factory(),
            'reference'         => 'TRF-' . strtoupper(fake()->unique()->bothify('######')),
            'status'            => 'pending',
            'notes'             => null,
            'shipped_at'        => null,
            'received_at'       => null,
        ];
    }
}
