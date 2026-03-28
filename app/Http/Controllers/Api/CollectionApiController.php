<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\PaymentProcessingService;
use App\Services\WebhookValidationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @group Collections & Payment
 *
 * Handles external Webhooks from Telco operators (M-Pesa, Tigo Pesa).
 */
class CollectionApiController extends Controller
{
    use ApiResponse;

    /**
     * Financial Webhook
     * 
     * Post a payload mapped from MNO integrations to automatically allocate installment payments.
     */
    public function webhook(Request $request, PaymentProcessingService $paymentService, WebhookValidationService $validator): JsonResponse
    {
        // Typically MNO webhooks have signatures
        Log::info("MNO Webhook Payload: " . json_encode($request->all()));

        $request->validate([
            'transaction_id' => 'required|string',
            'amount'         => 'required|numeric',
            'account_number' => 'required|string', // Usually the loan_number or NIDA
            'msisdn'         => 'required|string', // phone number
            'network'        => 'required|string',
        ]);

        $loan = Loan::where('loan_number', $request->account_number)->first();

        if (!$loan) {
            Log::warning("Webhook failed: Loan {$request->account_number} not found.");
            $validator->queueForManualReconciliation($request->all(), 'Account/Loan Not Found');
            return $this->errorResponse("Invalid account reference", 404);
        }

        if (!$validator->validateUniqueReference($request->transaction_id, $request->network)) {
            Log::warning("Replay Attack Blocked: Transaction {$request->transaction_id} already exists.");
            return $this->errorResponse("Duplicate transaction reference", 409);
        }

        try {
            // Apply payment waterfall dynamically using the engine
            $transaction = $paymentService->recordPayment(
                $loan, 
                (float) $request->amount, 
                $request->transaction_id, 
                $request->network
            );

            return $this->successResponse($transaction, "Payment captured and allocated successfully.");
        } catch (\Exception $e) {
            Log::error("Webhook error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
