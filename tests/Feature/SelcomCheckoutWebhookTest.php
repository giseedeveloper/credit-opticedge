<?php

use App\Models\SelcomPaymentRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config()->set('services.selcom.vendor', 'TILL61163918');
    config()->set('services.selcom.api_key', 'selcom-test-key');
    config()->set('services.selcom.api_secret', 'selcom-test-secret');
    config()->set('services.selcom.webhook_allowed_skew_seconds', 300);
    config()->set('services.selcom.webhook_replay_ttl_seconds', 900);
    Cache::flush();
});

test('selcom webhook rejects stale timestamps', function () {
    $payment = SelcomPaymentRequest::factory()->create([
        'order_id' => 'ORDER-STALE-001',
        'transid' => 'TRANS-STALE-001',
    ]);

    $payload = [
        'order_id' => $payment->order_id,
        'transid' => $payment->transid,
        'payment_status' => 'COMPLETED',
        'result' => 'SUCCESS',
        'resultcode' => '000',
    ];
    $signedFields = ['order_id', 'transid', 'payment_status', 'result', 'resultcode'];
    $timestamp = Carbon::now()->subMinutes(30)->toIso8601String();

    $this->postJson('/api/v1/payments/selcom/webhook', $payload, selcomHeaders($timestamp, $signedFields, $payload))
        ->assertStatus(401);
});

test('selcom webhook rejects replayed payload with same digest', function () {
    $payment = SelcomPaymentRequest::factory()->create([
        'order_id' => 'ORDER-REPLAY-001',
        'transid' => 'TRANS-REPLAY-001',
    ]);

    $payload = [
        'order_id' => $payment->order_id,
        'transid' => $payment->transid,
        'payment_status' => 'COMPLETED',
        'result' => 'SUCCESS',
        'resultcode' => '000',
    ];
    $signedFields = ['order_id', 'transid', 'payment_status', 'result', 'resultcode'];
    $timestamp = Carbon::now()->toIso8601String();
    $headers = selcomHeaders($timestamp, $signedFields, $payload);

    $this->postJson('/api/v1/payments/selcom/webhook', $payload, $headers)
        ->assertOk()
        ->assertJsonPath('meta.trace_id', fn ($traceId) => is_string($traceId) && $traceId !== '')
        ->assertJsonPath('meta.event', 'selcom_webhook.processed');
    $this->postJson('/api/v1/payments/selcom/webhook', $payload, $headers)->assertStatus(401);
});

/**
 * @param  array<int, string>  $signedFields
 * @param  array<string, mixed>  $payload
 * @return array<string, string>
 */
function selcomHeaders(string $timestamp, array $signedFields, array $payload): array
{
    return [
        'Authorization' => 'SELCOM '.base64_encode((string) config('services.selcom.api_key')),
        'Timestamp' => $timestamp,
        'Signed-Fields' => implode(',', $signedFields),
        'Digest' => selcomDigest($timestamp, $signedFields, $payload),
        'Digest-Method' => 'HS256',
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];
}

/**
 * @param  array<int, string>  $signedFields
 * @param  array<string, mixed>  $payload
 */
function selcomDigest(string $timestamp, array $signedFields, array $payload): string
{
    $segments = ['timestamp='.$timestamp];

    foreach ($signedFields as $field) {
        $segments[] = $field.'='.selcomSignedValue(data_get($payload, $field));
    }

    return base64_encode(hash_hmac(
        'sha256',
        implode('&', $segments),
        (string) config('services.selcom.api_secret'),
        true
    ));
}

function selcomSignedValue(mixed $value): string
{
    return match (true) {
        is_bool($value) => $value ? 'true' : 'false',
        is_scalar($value) => (string) $value,
        default => json_encode($value, JSON_UNESCAPED_SLASHES) ?: '',
    };
}
