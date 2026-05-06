<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SelcomCheckoutService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class SelcomWebhookController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request, SelcomCheckoutService $selcom): JsonResponse
    {
        $traceId = (string) str()->uuid();
        $payload = $request->all();

        Log::info('Selcom checkout callback received.', [
            'trace_id' => $traceId,
            'summary' => $this->summarizePayload($payload),
        ]);

        if (! $selcom->verifyWebhook($request)) {
            Log::warning('Selcom webhook rejected because the signature did not validate.', [
                'trace_id' => $traceId,
                'order_id' => $request->input('order_id'),
                'transid' => $request->input('transid'),
            ]);

            return $this->errorWithMeta(
                $this->errorResponse('Invalid Selcom webhook signature', 401),
                $traceId,
                'selcom_webhook.invalid_signature',
                'selcom_webhook.rejected'
            );
        }

        try {
            $payment = $selcom->markWebhookPayment($request->all());

            return $this->successWithMeta(
                $this->successResponse([
                    'order_id' => $payment->order_id,
                    'status' => $payment->status,
                    'payment_status' => $payment->payment_status,
                ], 'Selcom payment callback recorded.'),
                $traceId,
                'selcom_webhook.processed'
            );
        } catch (InvalidArgumentException $exception) {
            return $this->errorWithMeta(
                $this->errorResponse($exception->getMessage(), 422),
                $traceId,
                'selcom_webhook.invalid_payload',
                'selcom_webhook.failed'
            );
        } catch (Throwable $exception) {
            Log::error('Selcom webhook failed.', [
                'trace_id' => $traceId,
                'message' => $exception->getMessage(),
                'summary' => $this->summarizePayload($payload),
            ]);

            return $this->errorWithMeta(
                $this->errorResponse('Selcom callback processing failed.', 500),
                $traceId,
                'selcom_webhook.processing_error',
                'selcom_webhook.failed'
            );
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
            'order_id' => $payload['order_id'] ?? null,
            'transid' => $payload['transid'] ?? null,
            'result' => $payload['result'] ?? data_get($payload, 'data.0.result'),
            'resultcode' => $payload['resultcode'] ?? data_get($payload, 'data.0.resultcode'),
            'payment_status' => $payload['payment_status'] ?? data_get($payload, 'data.0.payment_status'),
            'msisdn' => $maskedPhone($payload['msisdn'] ?? data_get($payload, 'data.0.msisdn')),
            'reference' => $payload['reference'] ?? data_get($payload, 'data.0.reference'),
        ];
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
