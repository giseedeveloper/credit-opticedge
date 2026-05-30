<?php

namespace App\Http\Requests\Api\Kyc;

class Step5NokRequest extends KycAgentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nok_name' => ['required', 'string', 'min:2', 'max:100'],
            'nok_phone' => ['required', 'string', 'min:7', 'max:20'],
            'nok_phone_country' => ['required', 'string'],
            'nok_relationship' => ['required', 'string', 'max:60'],
            'nok2_name' => ['nullable', 'string', 'max:100'],
            'nok2_phone' => ['nullable', 'string', 'min:7', 'max:20'],
            'nok2_phone_country' => ['nullable', 'string'],
            'nok2_relationship' => ['nullable', 'string', 'max:60'],
        ];
    }
}
