<?php

namespace App\Http\Controllers\Api\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SelcomPaymentRequest;
use App\Services\SelcomCheckoutService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group Customer Portal — Payment
 *
 * Trigger Selcom USSD push for loan repayment and poll payment status.
 */
class CustomerPaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
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
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1000'],
            'phone' => ['nullable', 'string', 'min:7', 'max:20'],
        ]);

        $customer = $this->resolveCustomer($request);

        $loan = $customer->loans()->where('status', 'active')->latest()->first();

        if (! $loan) {
            return $this->errorResponse('No active loan found.', 404);
        }

        if (! $this->selcom->isConfigured()) {
            return $this->errorResponse('Payment service is not configured.', 503);
        }

        $paymentPhone = $request->phone
            ? $this->normalizePhone($request->string('phone')->toString())
            : $customer->phone;

        $orderId = 'REP-'.strtoupper(Str::random(8));

        $payment = SelcomPaymentRequest::create([
            'draft_reference' => $loan->loan_number,
            'customer_id' => $customer->id,
            'initiated_by' => null,
            'order_id' => $orderId,
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

            return $this->successResponse([
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'amount' => $payment->amount,
                'phone' => $paymentPhone,
                'status' => $payment->status,
            ], 'Payment request sent. Check your phone to confirm.');
        } catch (\Throwable $e) {
            $payment->update([
                'status' => 'failed',
                'result' => $e->getMessage(),
            ]);

            return $this->errorResponse('Payment initiation failed. Please try again.', 500);
        }
    }

    /**
     * Poll payment status.
     */
    public function status(Request $request, string $paymentId): JsonResponse
    {
        $customer = $this->resolveCustomer($request);

        $payment = SelcomPaymentRequest::where('id', $paymentId)
            ->where('customer_id', $customer->id)
            ->first();

        if (! $payment) {
            return $this->errorResponse('Payment not found.', 404);
        }

        if (in_array($payment->status, ['pending', 'processing']) && $payment->order_id) {
            try {
                $this->selcom->syncPaymentStatus($payment);
                $payment->refresh();
            } catch (\Throwable) {
                // Silently continue — status will be updated via webhook fallback.
            }
        }

        return $this->successResponse([
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'payment_status' => $payment->payment_status,
            'is_completed' => $payment->isCompleted(),
            'paid_at' => $payment->paid_at?->toDateTimeString(),
        ], 'Payment status retrieved.');
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
}
