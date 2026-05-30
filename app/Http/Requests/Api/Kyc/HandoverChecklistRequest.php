<?php

namespace App\Http\Requests\Api\Kyc;

class HandoverChecklistRequest extends KycAgentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'asset_handover_list' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'asset_handover_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
