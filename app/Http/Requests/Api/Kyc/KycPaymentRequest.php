<?php

namespace App\Http\Requests\Api\Kyc;

class KycPaymentRequest extends KycAgentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_phone' => ['required', 'string', 'min:7', 'max:20'],
            'payment_phone_country' => ['required', 'string', 'size:2'],
        ];
    }
}
