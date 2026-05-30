<?php

namespace App\Http\Requests\Api\Kyc;

class Step4IncomeRequest extends KycAgentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'occupation' => ['nullable', 'string', 'max:100'],
            'employer' => ['nullable', 'string', 'max:100'],
            'work_location' => ['nullable', 'string', 'max:200'],
            'monthly_income' => ['required', 'numeric', 'min:0'],
            'monthly_expenses' => ['nullable', 'numeric', 'min:0'],
            'income_payment_cycle' => ['nullable', 'in:weekly,biweekly,monthly,irregular'],
            'is_pep' => ['nullable', 'boolean'],
            'duration_at_work' => ['nullable', 'string', 'max:60'],
            'business_photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
