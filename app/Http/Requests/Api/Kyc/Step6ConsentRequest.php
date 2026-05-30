<?php

namespace App\Http\Requests\Api\Kyc;

class Step6ConsentRequest extends KycAgentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'terms_accepted' => ['required', 'accepted'],
            'data_consent_accepted' => ['required', 'accepted'],
            'call_consent_accepted' => ['required', 'accepted'],
        ];
    }
}
