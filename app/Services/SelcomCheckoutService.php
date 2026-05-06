<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SelcomPaymentRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SelcomCheckoutService
{
    public function isConfigured(): bool
    {
        return filled(config('services.selcom.vendor'))
            && filled(config('services.selcom.api_key'))
            && filled(config('services.selcom.api_secret'));
    }

    /**
     * @return array{configured: bool, base_url: string, vendor: string|null}
     */
    public function configurationSummary(): array
    {
        return [
            'configured' => $this->isConfigured(),
            'base_url' => rtrim((string) config('services.selcom.base_url', 'https://apigw.selcommobile.com'), '/'),
            'vendor' => config('services.selcom.vendor'),
        ];
    }

    /**
     * @param  array{name: string, phone: string, email?: string|null}  $buyer
     */
    public function initiateWalletPush(SelcomPaymentRequest $payment, array $buyer, string $callbackUrl): SelcomPaymentRequest
    {
        $this->ensureConfigured();

        $buyerPhone = $this->normalizeMsisdn($buyer['phone']);
        $createOrderPayload = [
            'vendor' => (string) config('services.selcom.vendor'),
            'order_id' => $payment->order_id,
            'buyer_email' => $buyer['email'] ?: "kyc-{$payment->order_id}@opticedge.local",
            'buyer_name' => $buyer['name'],
            'buyer_phone' => $buyerPhone,
            'amount' => number_format((float) $payment->amount, 2, '.', ''),
            'currency' => $payment->currency,
            'webhook' => base64_encode($callbackUrl),
            'buyer_remarks' => "KYC draft {$payment->draft_reference}",
            'merchant_remarks' => 'OpticEdge customer onboarding deposit',
            'no_of_items' => 1,
        ];

        $createOrderResponse = $this->postJson(
            '/v1/checkout/create-order-minimal',
            $createOrderPayload,
            array_keys($createOrderPayload)
        );

        $payment->forceFill([
            'request_payload' => $createOrderPayload,
            'response_payload' => $createOrderResponse,
            'status' => 'order_created',
            'result' => (string) ($createOrderResponse['result'] ?? 'SUCCESS'),
            'resultcode' => (string) ($createOrderResponse['resultcode'] ?? '000'),
            'selcom_reference' => $createOrderResponse['reference'] ?? null,
            'gateway_buyer_uuid' => data_get($createOrderResponse, 'data.0.buyer_uuid'),
            'payment_token' => data_get($createOrderResponse, 'data.0.payment_token'),
            'payment_gateway_url' => data_get($createOrderResponse, 'data.0.payment_gateway_url'),
        ])->save();

        $walletPayload = [
            'transid' => $payment->transid,
            'order_id' => $payment->order_id,
            'msisdn' => $buyerPhone,
        ];

        $walletResponse = $this->postJson(
            '/v1/checkout/wallet-payment',
            $walletPayload,
            array_keys($walletPayload)
        );

        return $this->applyGatewayState(
            $payment->fresh(),
            $walletResponse,
            source: 'response_payload',
            fallbackStatus: 'pending'
        );
    }

    public function syncPaymentStatus(SelcomPaymentRequest $payment): SelcomPaymentRequest
    {
        $this->ensureConfigured();

        $statusResponse = $this->getJson(
            '/v1/checkout/order-status',
            ['order_id' => $payment->order_id],
            ['order_id']
        );

        return $this->applyGatewayState(
            $payment,
            $statusResponse,
            source: 'status_payload',
            fallbackStatus: 'pending'
        );
    }

    /**
     * Poll Selcom order-status while the wallet payment is still in-flight (PENDING / INPROGRESS).
     * Aligns with Selcom guidance to query status after push USSD instead of trusting the initial HTTP ack.
     *
     * @see https://developers.selcommobile.com/#checkout-api (Get Order Status, INPROGRESS handling)
     */
    public function syncPaymentStatusWithShortPoll(
        SelcomPaymentRequest $payment,
        ?int $maxAttempts = null,
        ?int $sleepMs = null
    ): SelcomPaymentRequest {
        $maxAttempts ??= max(1, (int) config('services.selcom.payment_status_poll_max_attempts', 8));
        $sleepMs ??= max(0, (int) config('services.selcom.payment_status_poll_sleep_ms', 2000));

        $payment = $this->syncPaymentStatus($payment);

        for ($i = 1; $i < $maxAttempts && $this->shouldContinuePollingPayment($payment); $i++) {
            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
            $payment = $this->syncPaymentStatus($payment->fresh());
        }

        return $payment;
    }

    protected function shouldContinuePollingPayment(SelcomPaymentRequest $payment): bool
    {
        if ($payment->isCompleted() || $payment->status === 'failed') {
            return false;
        }

        $ps = strtoupper((string) $payment->payment_status);

        if ($ps === 'AMBIGUOUS') {
            return false;
        }

        if (in_array($ps, ['PENDING', 'INPROGRESS'], true)) {
            return true;
        }

        return $ps === ''
            && in_array($payment->status, ['pending', 'order_created', 'initiated'], true);
    }

    public function verifyWebhook(Request $request): bool
    {
        $this->ensureConfigured();

        $authorization = trim((string) $request->header('Authorization'));
        $expectedAuthorization = 'SELCOM '.base64_encode((string) config('services.selcom.api_key'));

        if (! hash_equals($expectedAuthorization, $authorization)) {
            return false;
        }

        $timestamp = trim((string) $request->header('Timestamp'));
        $signedFieldsHeader = trim((string) $request->header('Signed-Fields'));
        $providedDigest = trim((string) $request->header('Digest'));

        if ($timestamp === '' || $signedFieldsHeader === '' || $providedDigest === '') {
            return false;
        }

        try {
            $parsedTimestamp = Carbon::parse($timestamp);
        } catch (\Throwable) {
            return false;
        }
        $allowedSkew = max(30, (int) config('services.selcom.webhook_allowed_skew_seconds', 300));
        if (now()->diffInSeconds($parsedTimestamp, absolute: true) > $allowedSkew) {
            return false;
        }

        $signedFields = array_values(array_filter(array_map('trim', explode(',', $signedFieldsHeader))));
        $payload = $request->json()->all();

        if ($payload === []) {
            $payload = $request->all();
        }

        $expectedDigest = $this->buildDigest($timestamp, $signedFields, $payload);
        if (! hash_equals($expectedDigest, $providedDigest)) {
            return false;
        }

        $replayKeyMaterial = implode('|', [
            $providedDigest,
            (string) ($payload['order_id'] ?? ''),
            (string) ($payload['transid'] ?? ''),
        ]);
        $replayTtl = max(60, (int) config('services.selcom.webhook_replay_ttl_seconds', 900));
        $replayKey = 'selcom:webhook:'.hash('sha256', $replayKeyMaterial);

        return Cache::add($replayKey, true, now()->addSeconds($replayTtl));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function markWebhookPayment(array $payload): SelcomPaymentRequest
    {
        $orderId = $payload['order_id'] ?? null;
        $transId = $payload['transid'] ?? null;

        if (! filled($orderId) && ! filled($transId)) {
            throw new InvalidArgumentException('Selcom callback did not include order_id or transid.');
        }

        $payment = SelcomPaymentRequest::query()
            ->where(function ($query) use ($orderId, $transId): void {
                if (filled($orderId)) {
                    $query->where('order_id', $orderId);
                }

                if (filled($transId)) {
                    $query->orWhere('transid', $transId);
                }
            })
            ->firstOrFail();

        return $this->applyGatewayState(
            $payment,
            $payload,
            source: 'webhook_payload',
            fallbackStatus: 'pending',
            receivedViaWebhook: true
        );
    }

    public function attachToCustomer(SelcomPaymentRequest $payment, Customer $customer): SelcomPaymentRequest
    {
        $payment->customer()->associate($customer);
        $payment->save();

        $this->syncCustomerPaymentSnapshot($payment->fresh());

        return $payment->fresh();
    }

    public function createDraftPayment(string $draftReference, string $phone, float $amount, ?string $initiatedBy = null): SelcomPaymentRequest
    {
        return SelcomPaymentRequest::create([
            'draft_reference' => $draftReference,
            'initiated_by' => $initiatedBy,
            'order_id' => 'OE-KYC-'.Str::upper(Str::random(12)),
            'transid' => 'SEL-'.Str::upper(Str::random(16)),
            'phone' => $this->normalizeMsisdn($phone),
            'amount' => round($amount, 2),
            'currency' => 'TZS',
            'provider' => 'wallet-payment',
            'status' => 'initiated',
            'payment_status' => 'PENDING',
            'result' => 'PENDING',
            'resultcode' => '111',
        ]);
    }

    public function latestCompletedDraftPayment(string $draftReference): ?SelcomPaymentRequest
    {
        return SelcomPaymentRequest::query()
            ->where('draft_reference', $draftReference)
            ->where(function ($query): void {
                $query->where('payment_status', 'COMPLETED')
                    ->orWhere('status', 'completed');
            })
            ->latest('paid_at')
            ->latest()
            ->first();
    }

    public function findByAnyReference(string $reference): ?SelcomPaymentRequest
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        return SelcomPaymentRequest::query()
            ->where('selcom_reference', $reference)
            ->orWhere('order_id', $reference)
            ->orWhere('transid', $reference)
            ->first();
    }

    protected function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new InvalidArgumentException('Selcom Checkout is not configured. Set SELCOM_VENDOR, SELCOM_API_KEY, and SELCOM_API_SECRET in the backend .env file first.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $signedFields
     * @return array<string, mixed>
     */
    protected function postJson(string $path, array $payload, array $signedFields): array
    {
        $timestamp = Carbon::now()->toIso8601String();
        $response = Http::acceptJson()
            ->withHeaders($this->signedHeaders($timestamp, $signedFields, $payload))
            ->post($this->endpoint($path), $payload);

        return $this->decodeResponse($response);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<int, string>  $signedFields
     * @return array<string, mixed>
     */
    protected function getJson(string $path, array $query, array $signedFields): array
    {
        $timestamp = Carbon::now()->toIso8601String();
        $response = Http::acceptJson()
            ->withHeaders($this->signedHeaders($timestamp, $signedFields, $query))
            ->get($this->endpoint($path), $query);

        return $this->decodeResponse($response);
    }

    protected function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('services.selcom.base_url', 'https://apigw.selcommobile.com'), '/');

        if (Str::endsWith($baseUrl, '/v1') && Str::startsWith($path, '/v1/')) {
            $path = substr($path, 3);
        }

        return $baseUrl.$path;
    }

    /**
     * @param  array<int, string>  $signedFields
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    protected function signedHeaders(string $timestamp, array $signedFields, array $payload): array
    {
        return [
            'Authorization' => 'SELCOM '.base64_encode((string) config('services.selcom.api_key')),
            'Digest-Method' => 'HS256',
            'Digest' => $this->buildDigest($timestamp, $signedFields, $payload),
            'Timestamp' => $timestamp,
            'Signed-Fields' => implode(',', $signedFields),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * @param  array<int, string>  $signedFields
     * @param  array<string, mixed>  $payload
     */
    protected function buildDigest(string $timestamp, array $signedFields, array $payload): string
    {
        $segments = ['timestamp='.$timestamp];

        foreach ($signedFields as $field) {
            $value = data_get($payload, $field);
            $segments[] = $field.'='.$this->stringifySignedValue($value);
        }

        return base64_encode(hash_hmac(
            'sha256',
            implode('&', $segments),
            (string) config('services.selcom.api_secret'),
            true
        ));
    }

    protected function stringifySignedValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => json_encode($value, JSON_UNESCAPED_SLASHES) ?: '',
        };
    }

    protected function normalizeMsisdn(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (Str::startsWith($digits, '0')) {
            return '255'.ltrim($digits, '0');
        }

        if (Str::startsWith($digits, '255')) {
            return $digits;
        }

        if (strlen($digits) === 9) {
            return '255'.$digits;
        }

        return $digits;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function applyGatewayState(
        SelcomPaymentRequest $payment,
        array $payload,
        string $source,
        string $fallbackStatus = 'pending',
        bool $receivedViaWebhook = false
    ): SelcomPaymentRequest {
        $paymentStatus = $this->extractPaymentStatus($payload);
        $result = $this->extractResult($payload);
        $resultCode = (string) ($payload['resultcode'] ?? data_get($payload, 'data.0.resultcode') ?? '');

        if ($paymentStatus === '' && in_array($result, ['INPROGRESS', 'PENDING'], true)) {
            $paymentStatus = $result === 'INPROGRESS' ? 'INPROGRESS' : 'PENDING';
        }

        if ($paymentStatus === '') {
            $paymentStatus = 'PENDING';
        }

        // Selcom: wallet-payment / order-status may return result SUCCESS for "request accepted" while
        // payment_status is still PENDING. Only COMPLETED (or webhook COMPLETED) means money cleared.
        $resolvedStatus = match (true) {
            $paymentStatus === 'COMPLETED' => 'completed',
            in_array($paymentStatus, ['FAILED', 'CANCELLED', 'USERCANCELLED', 'REJECTED', 'DECLINED'], true) => 'failed',
            in_array($result, ['FAIL', 'FAILED', 'ERROR'], true) => 'failed',
            default => $fallbackStatus,
        };

        $payment->forceFill([
            $source => $payload,
            'phone' => $payload['phone'] ?? $payload['msisdn'] ?? data_get($payload, 'data.0.msisdn') ?? $payment->phone,
            'channel' => $payload['channel'] ?? data_get($payload, 'data.0.channel') ?? $payment->channel,
            'status' => $resolvedStatus,
            'payment_status' => $paymentStatus,
            'result' => $result !== '' ? $result : 'PENDING',
            'resultcode' => $resultCode !== '' ? $resultCode : '111',
            'selcom_reference' => $payload['reference'] ?? data_get($payload, 'data.0.reference') ?? $payment->selcom_reference,
            'gateway_buyer_uuid' => data_get($payload, 'data.0.buyer_uuid') ?? $payment->gateway_buyer_uuid,
            'payment_token' => data_get($payload, 'data.0.payment_token') ?? $payment->payment_token,
            'payment_gateway_url' => data_get($payload, 'data.0.payment_gateway_url') ?? $payment->payment_gateway_url,
            'paid_at' => match ($resolvedStatus) {
                'completed' => $payment->paid_at ?? now(),
                'failed' => null,
                default => $payment->paid_at,
            },
            'webhook_received_at' => $receivedViaWebhook ? now() : $payment->webhook_received_at,
        ])->save();

        $this->syncCustomerPaymentSnapshot($payment->fresh());

        return $payment->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractPaymentStatus(array $payload): string
    {
        $top = $payload['payment_status'] ?? null;
        $nested = data_get($payload, 'data.0.payment_status');
        $raw = $top ?? $nested ?? '';

        return strtoupper(trim((string) $raw));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractResult(array $payload): string
    {
        $top = $payload['result'] ?? null;
        $nested = data_get($payload, 'data.0.result');
        $raw = $top ?? $nested ?? '';

        return strtoupper(trim((string) $raw));
    }

    protected function syncCustomerPaymentSnapshot(SelcomPaymentRequest $payment): void
    {
        $customer = $payment->customer;

        if (! $customer) {
            return;
        }

        $customer->update([
            'deposit_payment_status' => $payment->isCompleted() ? 'completed' : $payment->status,
            'deposit_payment_amount' => $payment->amount,
            'deposit_payment_reference' => $payment->selcom_reference ?: $payment->transid,
            'deposit_paid_at' => $payment->paid_at,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeResponse($response): array
    {
        try {
            $response->throw();
        } catch (RequestException $exception) {
            $payload = $response->json();
            $message = is_array($payload) ? ($payload['message'] ?? $payload['result'] ?? 'Selcom request failed.') : 'Selcom request failed.';
            throw new InvalidArgumentException((string) $message, previous: $exception);
        }

        return $response->json() ?? [];
    }
}
