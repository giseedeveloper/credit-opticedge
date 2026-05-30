<?php

namespace App\Http\Requests\Api\Kyc;

use Illuminate\Validation\Rule;

class Step2IdentityRequest extends KycAgentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $customerId = $this->route('customer_id');

        return [
            'first_name' => ['required', 'string', 'min:2', 'max:60'],
            'middle_name' => ['nullable', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'min:2', 'max:60'],
            'gender' => ['required', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nida_number' => [
                'required',
                'string',
                'size:20',
                Rule::unique('customers', 'nida_number')->ignore($customerId),
            ],
            'id_type' => ['required', 'in:nida,passport,driving_license,voter_card'],
            'id_front_photo' => ['nullable', 'image', 'max:5120'],
            'id_back_photo' => ['nullable', 'image', 'max:5120'],
            'headshot_photo' => ['nullable', 'image', 'max:5120'],
            'client_fo_photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
