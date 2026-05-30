<?php

namespace App\Http\Requests\Api\Kyc;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

abstract class KycAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $customerId = $this->route('customer_id') ?? $this->route('id');

        if ($customerId === null) {
            return true;
        }

        $query = Customer::query();

        if (! $user->isAdmin()) {
            $query->where('registered_by', $user->id);
        }

        return $query->whereKey($customerId)->exists();
    }
}
