<?php

namespace App\Http\Requests\Api\Kyc;

class Step1DeviceRequest extends KycAgentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'phone_model_id' => ['nullable', 'exists:phone_models,id'],
            'inventory_unit_id' => ['nullable', 'exists:inventory_units,id'],
            'device_specs' => ['required_without:phone_model_id', 'nullable', 'string', 'min:3', 'max:200'],
            'imei_number' => ['nullable', 'string', 'digits:15'],
            'imei_2' => ['nullable', 'string', 'digits:15'],
            'serial_number' => ['nullable', 'string', 'max:60'],
            'cash_price' => ['required_without:phone_model_id', 'nullable', 'numeric', 'min:1'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'preferred_repayment' => ['required', 'in:weekly,biweekly,monthly'],
            'loan_interest_rate' => ['nullable', 'numeric', 'min:0'],
            'loan_interest_type' => ['nullable', 'in:flat,reducing_balance'],
            'loan_duration_weeks' => ['nullable', 'integer', 'min:1', 'max:260'],
            'loan_grace_period_days' => ['nullable', 'integer', 'min:0', 'max:60'],
            'imei_photo' => ['nullable', 'image', 'max:5120'],
            'device_box_photo' => ['nullable', 'image', 'max:5120'],
            'device_photo' => ['nullable', 'image', 'max:5120'],
            'device_scan' => ['nullable', 'array'],
            'accessories' => ['nullable', 'array', 'max:8'],
            'accessories.*.code' => ['nullable', 'string', 'max:60'],
            'accessories.*.name' => ['nullable', 'string', 'max:60'],
            'accessories.*.quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
            'accessories.*.offer_type' => ['nullable', 'in:free,charged,discounted'],
            'accessories.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'accessories.*.notes' => ['nullable', 'string', 'max:160'],
            'store_offer_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
