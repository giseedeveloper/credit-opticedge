<?php

namespace App\Http\Requests\Api\Kyc;

class PreHandoverChecklistRequest extends KycAgentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'device_unboxed' => ['required', 'boolean', 'accepted'],
            'device_boot_verified' => ['required', 'boolean', 'accepted'],
            'mdm_lock_confirmed' => ['required', 'boolean', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'device_unboxed.accepted' => 'Confirm the device was unboxed in front of the customer.',
            'device_boot_verified.accepted' => 'Confirm the device powered on successfully.',
            'mdm_lock_confirmed.accepted' => 'Confirm MDM lock was applied before handover.',
        ];
    }
}
