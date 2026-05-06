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
        $traceId = (string) str()->uuid();
        Log::info('MNO Webhook Payload received', [
            'trace_id' => $traceId,
            'summary' => $this->summarizePayload($request->all()),
        ]);

        $request->validate([
            'transaction_id' => 'required|string',
            'amount' => 'required|numeric',
            'account_number' => 'required|string',
            'msisdn' => 'required|string',
            'network' => 'required|string',
        ]);

        if (! $validator->verifySignature($request, config('services.collections.webhook_secret'))) {
            Log::warning('Webhook rejected because the signature did not validate.', [
                'trace_id' => $traceId,
                'transaction_id' => $request->transaction_id,
            ]);

            return $this->errorResponse('Invalid webhook signature', 401);
        }

        $loan = Loan::where('loan_number', $request->account_number)->first();

        if (! $loan) {
            Log::warning('Webhook failed: loan account not found.', [
                'trace_id' => $traceId,
                'transaction_id' => $request->transaction_id,
                'account_number' => $request->account_number,
            ]);
            $validator->queueForManualReconciliation($request->all(), 'Account/Loan Not Found');

            return $this->errorResponse('Invalid account reference', 404);
        }

        if (! $validator->validateUniqueReference($request->transaction_id)) {
            Log::warning('Replay attack blocked: transaction already exists.', [
                'trace_id' => $traceId,
                'transaction_id' => $request->transaction_id,
            ]);

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
            Log::error('Webhook error.', [
                'trace_id' => $traceId,
                'message' => $e->getMessage(),
                'summary' => $this->summarizePayload($request->all()),
            ]);

            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function summarizePayload(array $payload): array
    {
        $maskedPhone = function ($value): ?string {
            $digits = preg_replace('/\D+/', '', (string) $value);
            if (! $digits) {
                return null;
            }
            if (strlen($digits) <= 4) {
                return str_repeat('*', strlen($digits));
            }

            return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
        };

        return [
            'transaction_id' => $payload['transaction_id'] ?? null,
            'amount' => $payload['amount'] ?? null,
            'account_number' => $payload['account_number'] ?? null,
            'network' => $payload['network'] ?? null,
            'msisdn' => $maskedPhone($payload['msisdn'] ?? null),
        ];
    }
}
