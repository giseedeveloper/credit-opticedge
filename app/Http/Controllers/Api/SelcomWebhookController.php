<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SelcomCheckoutService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SelcomWebhookController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request, SelcomCheckoutService $selcom): JsonResponse
    {
        Log::info('Selcom checkout callback received.', $request->all());

        if (! $selcom->verifyWebhook($request)) {
            Log::warning('Selcom webhook rejected because the signature did not validate.', [
                'order_id' => $request->input('order_id'),
                'transid' => $request->input('transid'),
            ]);

            return $this->errorResponse('Invalid Selcom webhook signature', 401);
        }

        try {
            $payment = $selcom->markWebhookPayment($request->all());

            return $this->successResponse([
                'order_id' => $payment->order_id,
                'status' => $payment->status,
                'payment_status' => $payment->payment_status,
            ], 'Selcom payment callback recorded.');
        } catch (InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            Log::error('Selcom webhook failed.', [
                'message' => $exception->getMessage(),
                'payload' => $request->all(),
            ]);

            return $this->errorResponse('Selcom callback processing failed.', 500);
        }
    }
}
