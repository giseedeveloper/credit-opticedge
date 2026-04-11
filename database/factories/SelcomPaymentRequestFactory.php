<?php

namespace Database\Factories;

use App\Models\SelcomPaymentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SelcomPaymentRequest>
 */
class SelcomPaymentRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'draft_reference' => fake()->uuid(),
            'customer_id' => null,
            'initiated_by' => User::factory(),
            'order_id' => strtoupper(fake()->bothify('ORD-#####??')),
            'transid' => strtoupper(fake()->bothify('TRX-#####??')),
            'phone' => '2557'.fake()->numerify('########'),
            'amount' => fake()->numberBetween(10_000, 250_000),
            'currency' => 'TZS',
            'provider' => 'wallet-payment',
            'channel' => null,
            'status' => 'pending',
            'payment_status' => 'PENDING',
            'result' => 'PENDING',
            'resultcode' => '111',
            'selcom_reference' => null,
            'gateway_buyer_uuid' => null,
            'payment_token' => null,
            'payment_gateway_url' => null,
            'request_payload' => null,
            'response_payload' => null,
            'status_payload' => null,
            'webhook_payload' => null,
            'paid_at' => null,
            'webhook_received_at' => null,
        ];
    }
}
