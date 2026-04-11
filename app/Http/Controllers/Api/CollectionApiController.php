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
use InvalidArgumentException;

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
        Log::info('MNO Webhook Payload received', $request->all());

        $request->validate([
            'transaction_id' => 'required|string',
            'amount' => 'required|numeric',
            'account_number' => 'required|string',
            'msisdn' => 'required|string',
            'network' => 'required|string',
        ]);

        if (! $validator->verifySignature($request, config('services.collections.webhook_secret'))) {
            Log::warning('Webhook rejected because the signature did not validate.', [
                'transaction_id' => $request->transaction_id,
            ]);

            return $this->errorResponse('Invalid webhook signature', 401);
        }

        $loan = Loan::where('loan_number', $request->account_number)->first();

        if (! $loan) {
            Log::warning("Webhook failed: Loan {$request->account_number} not found.");
            $validator->queueForManualReconciliation($request->all(), 'Account/Loan Not Found');

            return $this->errorResponse('Invalid account reference', 404);
        }

        if (! $validator->validateUniqueReference($request->transaction_id)) {
            Log::warning("Replay Attack Blocked: Transaction {$request->transaction_id} already exists.");

            return $this->errorResponse('Duplicate transaction reference', 409);
        }

        try {
            $transaction = $paymentService->recordPayment(
                $loan,
                (float) $request->amount,
                $request->transaction_id,
                $request->network,
                [
                    'msisdn' => $request->msisdn,
                    'network' => $request->network,
                    'account_number' => $request->account_number,
                    'external_reference' => $request->transaction_id,
                    'description' => "Webhook repayment via {$request->network}",
                ]
            );

            return $this->successResponse($transaction, 'Payment captured and allocated successfully.');
        } catch (InvalidArgumentException $e) {
            $status = str_contains($e->getMessage(), 'Duplicate') ? 409 : 422;

            return $this->errorResponse($e->getMessage(), $status);
        } catch (\Exception $e) {
            Log::error('Webhook error: '.$e->getMessage());

            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
