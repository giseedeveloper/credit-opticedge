<?php

namespace App\Http\Requests\Api\Kyc;

class Step7SubmitRequest extends KycAgentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fo_notes' => ['nullable', 'string', 'max:1000'],
            'application_source' => ['nullable', 'in:walk_in,referral,vendor,social_media,agent'],
            'agreement_decision' => ['required', 'in:yes,no'],
            'payment_phone' => ['nullable', 'string', 'max:20'],
            'loan_term_months' => ['nullable', 'integer', 'min:1', 'max:60'],
            'downpayment_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_signature' => ['nullable', 'string'],
            'fo_signature' => ['nullable', 'string'],
            'etr_receipt_photo' => ['nullable', 'image', 'max:5120'],
            'asset_handover_list' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'asset_handover_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
