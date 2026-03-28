<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group KYC Verification
 *
 * Agents upload customer KYC documents via Mobile App.
 */
class KycApiController extends Controller
{
    use ApiResponse;

    /**
     * Upload NIDA
     * 
     * Expects a photo of the NIDA ID card.
     */
    public function uploadNida(Request $request, string $customerId): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:10240',
        ]);

        $customer = Customer::findOrFail($customerId);

        $customer->addMediaFromRequest('file')
                 ->toMediaCollection('nida_documents');

        return $this->successResponse([], 'NIDA document uploaded successfully');
    }

    /**
     * Submit Selfies
     * 
     * Handles front and back selfies or live capture frames.
     */
    public function uploadPhoto(Request $request, string $customerId): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:10240',
        ]);

        $customer = Customer::findOrFail($customerId);

        $customer->addMediaFromRequest('file')
                 ->toMediaCollection('selfies');

        return $this->successResponse([], 'Selfie uploaded successfully');
    }

    /**
     * Finalize Verification
     * 
     * Signals that the agent has finished KYC capture.
     */
    public function finalizeVerification(Request $request, string $customerId): JsonResponse
    {
        $customer = Customer::findOrFail($customerId);

        // Verification logic, e.g. marking the KYC status as "pending_approval"
        if (!$customer->nida_number) {
            return $this->errorResponse("A valid NIDA number is required to finalize KYC.", 422);
        }

        $customer->update(['status' => 'active']);

        return $this->successResponse($customer, 'KYC submitted for review.');
    }
}
