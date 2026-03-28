<?php

namespace Database\Factories;

use App\Models\PhoneModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhoneModel>
 */
class PhoneModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->bothify('Model-??###');

        return [
            'brand_id'       => \App\Models\Brand::factory(),
            'name'           => $name,
            'slug'           => \Illuminate\Support\Str::slug($name),
            'retail_price'   => fake()->numberBetween(200_000, 2_000_000),
            'cost_price'     => fake()->numberBetween(150_000, 1_800_000),
            'specifications' => ['storage' => '128GB', 'ram' => '6GB', 'color' => 'black'],
            'is_active'      => true,
        ];
    }
}
