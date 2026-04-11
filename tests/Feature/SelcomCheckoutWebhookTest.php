<?php

use App\Models\SelcomPaymentRequest;
use App\Models\User;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('services.selcom.base_url', 'https://apigw.selcommobile.com/v1');
    Config::set('services.selcom.vendor', 'OPTICEDGE');
    Config::set('services.selcom.api_key', 'selcom-test-key');
    Config::set('services.selcom.api_secret', 'selcom-test-secret');
});

it('rejects selcom callbacks with invalid digests', function () {
    SelcomPaymentRequest::factory()->create([
        'initiated_by' => User::factory(),
        'order_id' => 'OE-KYC-ORDER-001',
        'transid' => 'SEL-TRANS-001',
    ]);

    $payload = [
        'order_id' => 'OE-KYC-ORDER-001',
        'transid' => 'SEL-TRANS-001',
        'result' => 'SUCCESS',
        'resultcode' => '000',
        'payment_status' => 'COMPLETED',
        'reference' => 'SEL-REF-001',
        'amount' => '85000.00',
        'phone' => '255712345678',
    ];

    $response = $this->withHeaders([
        'Authorization' => 'SELCOM '.base64_encode('selcom-test-key'),
        'Timestamp' => now()->toIso8601String(),
        'Signed-Fields' => 'order_id,transid,result,resultcode,payment_status,reference,amount,phone',
        'Digest' => 'invalid-digest',
    ])->postJson('/api/v1/payments/selcom/webhook', $payload);

    $response->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Invalid Selcom webhook signature');
});

it('records a valid selcom callback and marks the payment as completed', function () {
    $payment = SelcomPaymentRequest::factory()->create([
        'initiated_by' => User::factory(),
        'order_id' => 'OE-KYC-ORDER-002',
        'transid' => 'SEL-TRANS-002',
        'status' => 'pending',
        'payment_status' => 'PENDING',
        'result' => 'PENDING',
        'resultcode' => '111',
    ]);

    $payload = [
        'order_id' => 'OE-KYC-ORDER-002',
        'transid' => 'SEL-TRANS-002',
        'result' => 'SUCCESS',
        'resultcode' => '000',
        'payment_status' => 'COMPLETED',
        'reference' => 'SEL-REF-002',
        'amount' => '85000.00',
        'phone' => '255712345678',
        'channel' => 'AIRTELMONEY',
    ];

    $timestamp = now()->toIso8601String();
    $signedFields = ['order_id', 'transid', 'result', 'resultcode', 'payment_status', 'reference', 'amount', 'phone', 'channel'];

    $response = $this->withHeaders([
        'Authorization' => 'SELCOM '.base64_encode('selcom-test-key'),
        'Timestamp' => $timestamp,
        'Signed-Fields' => implode(',', $signedFields),
        'Digest' => selcomWebhookDigest($payload, $signedFields, $timestamp, 'selcom-test-secret'),
    ])->postJson('/api/v1/payments/selcom/webhook', $payload);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.order_id', $payment->order_id)
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.payment_status', 'COMPLETED');

    $payment->refresh();

    expect($payment->status)->toBe('completed')
        ->and($payment->payment_status)->toBe('COMPLETED')
        ->and($payment->selcom_reference)->toBe('SEL-REF-002')
        ->and($payment->webhook_received_at)->not->toBeNull()
        ->and($payment->paid_at)->not->toBeNull();
});

function selcomWebhookDigest(array $payload, array $signedFields, string $timestamp, string $secret): string
{
    $segments = ['timestamp='.$timestamp];

    foreach ($signedFields as $field) {
        $segments[] = $field.'='.selcomSignedStringValue(data_get($payload, $field));
    }

    return base64_encode(hash_hmac('sha256', implode('&', $segments), $secret, true));
}

function selcomSignedStringValue(mixed $value): string
{
    return match (true) {
        is_bool($value) => $value ? 'true' : 'false',
        is_scalar($value) => (string) $value,
        default => json_encode($value, JSON_UNESCAPED_SLASHES) ?: '',
    };
}
