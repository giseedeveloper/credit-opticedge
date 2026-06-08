<?php

namespace App\Http\Requests\Api\Kyc;

use App\Services\KycIdentityDocumentRules;

class Step2IdentityRequest extends KycAgentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $customerId = $this->route('customer_id');
        $identityRules = app(KycIdentityDocumentRules::class);

        return [
            'first_name' => ['required', 'string', 'min:2', 'max:60'],
            'middle_name' => ['nullable', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'min:2', 'max:60'],
            'gender' => ['required', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nida_number' => $identityRules->documentNumberRules(
                $this->string('id_type')->toString() ?: null,
                $customerId
            ),
            'id_type' => ['required', 'in:nida,passport,driving_license,voter_card'],
            'id_front_photo' => ['nullable', 'image', 'max:5120'],
            'id_back_photo' => ['nullable', 'image', 'max:5120'],
            'headshot_photo' => ['nullable', 'image', 'max:5120'],
            'client_fo_photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
