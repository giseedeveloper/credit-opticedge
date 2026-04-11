<?php

namespace Database\Factories;

use App\Models\SystemDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemDocument>
 */
class SystemDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'kyc_customer_agreement',
            'title' => 'Customer Device Agreement',
            'disk' => 'public',
            'path' => 'agreements/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'is_active' => true,
            'uploaded_by' => User::factory(),
            'metadata' => ['original_name' => 'agreement.pdf'],
        ];
    }
}
