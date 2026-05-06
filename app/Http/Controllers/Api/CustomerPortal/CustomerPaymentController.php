<?php

namespace App\Http\Controllers\Api\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SelcomPaymentRequest;
use App\Services\CustomerLoanProvisioningService;
use App\Services\SelcomCheckoutService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * @group Customer Portal — Payment
 *
 * Trigger Selcom USSD push for loan repayment and poll payment status.
 */
class CustomerPaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CustomerLoanProvisioningService $loanProvisioning,
        private SelcomCheckoutService $selcom,
    ) {}

    /**
     * Initiate a repayment via Selcom USSD push.
     *
     * The customer specifies the amount and phone to pay from.
     * The Selcom USSD push is sent to the customer's mobile money wallet.
     */
    public function pay(Request $request): JsonResponse
    {
        $traceId = $this->newTraceId();
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1000'],
            'phone' => ['nullable', 'string', 'min:7', 'max:20'],
            'idempotency_key' => ['nullable', 'string', 'min:8', 'max:80'],
        ]);

        $customer = $this->resolveCustomer($request);

        $loan = $customer->loans()->where('status', 'active')->latest()->first()
            ?? $this->loanProvisioning->provisionForCustomerPortal($customer);

        if (! $loan) {
            $message = $customer->isAssetReleased()
                ? 'Your device has been released, but your loan account is still being prepared.'
                : 'No active loan found.';

            return $this->errorWithMeta(
                $this->errorResponse($message, 409),
                $traceId,
                'customer_payment.loan_not_ready',
                'customer_payment.pay_failed'
            );
        }

        if (! $this->selcom->isConfigured()) {
            return $this->errorWithMeta(
                $this->errorResponse('Payment service is not configured.', 503),
                $traceId,
                'customer_payment.service_unavailable',
                'customer_payment.pay_failed'
            );
        }

        $paymentPhone = $request->phone
            ? $this->normalizePhone($request->string('phone')->toString())
            : (string) ($customer->phone ?? '');

        if ($paymentPhone === '') {
            return $this->errorWithMeta(
                $this->errorResponse('Namba ya simu inahitajika kwa malipo ya M-Pesa.', 422),
                $traceId,
                'customer_payment.phone_required',
                'customer_payment.pay_failed'
            );
        }

        $idempotencyKey = trim((string) $request->input('idempotency_key', ''));
        $orderId = $idempotencyKey !== ''
            ? 'REP-'.strtoupper(substr(hash('sha256', $customer->id.'|'.$idempotencyKey), 0, 20))
            : 'REP-'.strtoupper(Str::random(8));

        $existing = SelcomPaymentRequest::query()
            ->where('customer_id', $customer->id)
            ->where('order_id', $orderId)
            ->first();

        if ($existing) {
            return $this->successWithMeta(
                $this->successResponse([
                    'payment_id' => $existing->id,
                    'order_id' => $existing->order_id,
                    'amount' => $existing->amount,
                    'phone' => $existing->phone,
                    'status' => $existing->status,
                    'idempotent_replay' => true,
                ], 'Payment request already exists. Reusing existing request.'),
                $traceId,
                'customer_payment.idempotent_reused'
            );
        }

        $payment = SelcomPaymentRequest::create([
            'draft_reference' => $loan->loan_number,
            'customer_id' => $customer->id,
            'initiated_by' => null,
            'order_id' => $orderId,
            'transid' => 'SEL-'.strtoupper(Str::random(16)),
            'phone' => $paymentPhone,
            'amount' => $request->amount,
            'currency' => 'TZS',
            'provider' => 'Selcom',
            'channel' => 'customer_app',
            'status' => 'pending',
        ]);

        try {
            $callbackUrl = url('/api/v1/payments/selcom/webhook');

            $payment = $this->selcom->initiateWalletPush($payment, [
                'name' => $customer->full_name,
                'phone' => $paymentPhone,
                'email' => $customer->email,
            ], $callbackUrl);

            return $this->successWithMeta(
                $this->successResponse([
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'amount' => $payment->amount,
                    'phone' => $paymentPhone,
                    'status' => $payment->status,
                ], 'Payment request sent. Check your phone to confirm.'),
                $traceId,
                'customer_payment.requested'
            );
        } catch (Throwable $e) {
            report($e);

            $payment->update([
                'status' => 'failed',
                'result' => $e->getMessage(),
            ]);

            return $this->errorWithMeta(
                $this->errorResponse('Payment initiation failed. Please try again.', 500),
                $traceId,
                'customer_payment.initiation_failed',
                'customer_payment.pay_failed'
            );
        }
    }

    /**
     * Poll payment status.
     */
    public function status(Request $request, string $paymentId): JsonResponse
    {
        $traceId = $this->newTraceId();
        $customer = $this->resolveCustomer($request);

        $payment = SelcomPaymentRequest::where('id', $paymentId)
            ->where('customer_id', $customer->id)
            ->first();

        if (! $payment) {
            return $this->errorWithMeta(
                $this->errorResponse('Payment not found.', 404),
                $traceId,
                'customer_payment.not_found',
                'customer_payment.status_failed'
            );
        }

        if (in_array($payment->status, ['pending', 'processing']) && $payment->order_id) {
            try {
                $this->selcom->syncPaymentStatus($payment);
                $payment->refresh();
            } catch (Throwable) {
                // Silently continue — status will be updated via webhook fallback.
            }
        }

        return $this->successWithMeta(
            $this->successResponse([
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'payment_status' => $payment->payment_status,
                'is_completed' => $payment->isCompleted(),
                'paid_at' => $payment->paid_at?->toDateTimeString(),
            ], 'Payment status retrieved.'),
            $traceId,
            'customer_payment.status_checked'
        );
    }

    private function resolveCustomer(Request $request): Customer
    {
        $tokenable = $request->user('sanctum');

        abort_unless($tokenable instanceof Customer, 401, 'Unauthorized.');

        return $tokenable;
    }

    private function normalizePhone(string $raw): string
    {
        $phone = preg_replace('/[^0-9]/', '', $raw);

        if (str_starts_with($phone, '0') && strlen($phone) >= 10) {
            $phone = '255'.substr($phone, 1);
        } elseif (strlen($phone) === 9) {
            $phone = '255'.$phone;
        }

        return $phone;
    }

    private function newTraceId(): string
    {
        return (string) str()->uuid();
    }

    private function successWithMeta(JsonResponse $response, string $traceId, string $event): JsonResponse
    {
        $payload = $response->getData(true);
        $payload['meta'] = [
            'trace_id' => $traceId,
            'event' => $event,
        ];
        $response->setData($payload);

        return $response;
    }

    private function errorWithMeta(
        JsonResponse $response,
        string $traceId,
        string $errorCode,
        string $event
    ): JsonResponse {
        $payload = $response->getData(true);
        $payload['meta'] = [
            'trace_id' => $traceId,
            'event' => $event,
            'error_code' => $errorCode,
        ];
        $response->setData($payload);

        return $response;
    }
}
